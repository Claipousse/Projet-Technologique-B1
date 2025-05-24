<?php
session_start();
$servername = "localhost";
$username = "root"; //nom utilisateur, pas sur s'il faut l'intégrer avant dans la table utilisateur
$password = "root"; //Mot de passe vide, également changé sur PhpMyAdmin
$dbname = "BDD_pistache"; //Nom de la BDD

// Fonction pour se connecter à la base de données
function connexionBDD() {
    global $servername, $username, $password, $dbname;
    //Permet une meilleure gestion des erreurs
    try {
        //On crée un PDO (PHP Data Object) qui établit une connexion avec la BDD (en reprenant les infos du dessus)
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        //S'il y a des exceptions, met une erreur
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //Encode avec UTF-8, pour gérer les caractères spéciaux
        $conn->exec("SET NAMES utf8");
        //Si tout est OK, renvoi le PDO pour l'utiliser ailleurs dans le code
        return $conn;
    } catch(PDOException $e) { //Si pas OK, on s'arrête et on envoi un message d'erreur
        die("Erreur de connexion : " . $e->getMessage()); //die arrête le processus
    }
}

// Fonction pour vérifier si l'utilisateur est connecté
function estConnecte() {
    return !empty($_SESSION['id_utilisateur']);
}

// Fonction pour vérifier si l'utilisateur est un administrateur
function estAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] == 'admin';
}

// Fonction pour rediriger vers une autre page
function rediriger($url) {
    header("Location: $url");
    exit;
}

// Fonction pour supprimer automatiquement les événements terminés
function supprimerEvenementsTermines() {
    try {
        $conn = connexionBDD();
        $conn->beginTransaction();

        // Récupérer les IDs des événements terminés
        $stmt = $conn->prepare("SELECT id_evenement FROM evenement WHERE date_fin < CURDATE()");
        $stmt->execute();
        $evenements_termines = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($evenements_termines)) {
            $placeholders = implode(',', array_fill(0, count($evenements_termines), '?'));

            // Supprimer les préférences liées aux inscriptions de ces événements
            $stmt = $conn->prepare("
                DELETE p FROM preferences p 
                JOIN inscription i ON p.id_inscription = i.id_inscription 
                WHERE i.id_evenement IN ($placeholders)
            ");
            $stmt->execute($evenements_termines);

            // Supprimer les inscriptions
            $stmt = $conn->prepare("DELETE FROM inscription WHERE id_evenement IN ($placeholders)");
            $stmt->execute($evenements_termines);

            // Supprimer les associations jeux-événement
            $stmt = $conn->prepare("DELETE FROM jeux_evenement WHERE id_evenement IN ($placeholders)");
            $stmt->execute($evenements_termines);

            // Supprimer les événements
            $stmt = $conn->prepare("DELETE FROM evenement WHERE id_evenement IN ($placeholders)");
            $stmt->execute($evenements_termines);
        }

        $conn->commit();
        return count($evenements_termines);
    } catch (PDOException $e) {
        if (isset($conn)) {
            $conn->rollBack();
        }
        error_log("Erreur lors de la suppression des événements terminés : " . $e->getMessage());
        return false;
    }
}

/* Remarque : On pourrait ajouter des fonctions pour hasher les données sensibles,
notamment les mots de passes. C'est pas demandé mais c'est toujours un plus */