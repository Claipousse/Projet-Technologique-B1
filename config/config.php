<?php
session_start();
$servername = "localhost";
$username = "root"; //nom utilisateur, pas sur s'il faut l'intégrer avant dans la table utilisateur
$password = ""; //mdp
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
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Fonction pour vérifier si l'utilisateur est un administrateur
function estAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Fonction pour rediriger vers une autre page
function rediriger($url) {
    header("Location: $url");
    exit;
}

/* Remarque : On pourrait ajouter des fonctions pour hasher les données sensibles,
notamment les mots de passes. C'est pas demandé mais c'est toujours un plus */