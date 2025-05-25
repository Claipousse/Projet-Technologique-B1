<?php

/*Ce fichier sert à répertorier de nombreuses fonctions que nous appellerons dans les autres fichiers .php, comme
la plupart des fonctions seront utilisés plusieurs fois, s'il y a un problème avec l'une d'elle, il suffira de modifier
ce fichier pour patcher le problème dans tous les autres fichiers, au lieu de modifier chaques fichiers où la fonction
est présente.*/

require_once __DIR__ . "/../config/config.php";

// Fonction pour déterminer le chemin de base selon la profondeur
function getBasePath() {
    // Récupérer le chemin du script actuel
    $scriptPath = $_SERVER['SCRIPT_NAME'];

    // Si on est dans un sous-dossier du projet (auth/ ou user/)
    if (strpos($scriptPath, '/auth/') !== false || strpos($scriptPath, '/user/') !== false) {
        return '../';
    } else {
        // On est à la racine du projet, pas de préfixe nécessaire
        return '';
    }
}

/* Genère une alerte HTML à afficher, $message: message à afficher, $type: success, danger, warning */
function alerte($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '" role="alert">
    ' . $message . '
    <button type="button" class="btn-close" data-dismiss="alert" aria-label="Fermer"></button>
    </div>';
}

// Vérifie si une chaîne est vide ou nulle (true si vide, sinon false)
function estVide($chaine) {
    return (!isset($chaine) || empty(trim($chaine)));
}

//Redirige vers une url avec un message
function redirigerAvecMessage($url, $message, $type = 'success') {
    // Déterminer le séparateur (? ou &) selon si l'URL a déjà des paramètres
    $separator = strpos($url, '?') !== false ? '&' : '?';

    // Ajouter les paramètres message et type à l'URL
    $url .= $separator . 'message=' . urlencode($message) . '&type=' . urlencode($type);

    rediriger($url); //rediriger() est une fonction de config.php
}

//Savoir si un jeu existe dans la BDD
function jeuExiste($nom, $id = null) //id permet d'exclure un jeu de la recherche
{
    $conn = connexionBDD(); //connexionBDD() vient de config.php
    $sql = "SELECT id_jeux FROM jeux WHERE nom = :nom"; //Cherche dans la table jeu si le nom d'un jeu à la valeur de $nom
    $params = [':nom' => $nom]; //Crée un tableau où :nom prend la valeur du paramètre $nom (jeu que l'on cherche)

    if ($id !== null) { //Si id fourni, on ajoute une instruction pour dégager ce jeu de la recherche
        $sql .= " AND id_jeux != :id";
        $params[':id'] = $id;
    }

    $stmt = $conn->prepare($sql); //prépare la requête
    $stmt->execute($params); //éxécute la requête avec les paramètres définis

    return $stmt->rowCount() > 0; //si au moins 1 jeu à été trouvé true, sinon false
}

// Fonction pour formater les dates d'événements en français
if (!function_exists('formaterDateEvenement')) {
    function formaterDateEvenement($date_debut, $date_fin, $avec_le = false) {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $debut = new DateTime($date_debut);
        $fin = new DateTime($date_fin);

        $prefixe = $avec_le ? "Le " : "";

        if ($debut->format('Y-m-d') === $fin->format('Y-m-d')) {
            // Même jour
            return $prefixe . $debut->format('j') . " " . $mois[(int)$debut->format('n')];
        } else {
            // Jours différents
            $jour_debut = $debut->format('j');
            $jour_fin = $fin->format('j');
            $mois_fin = $mois[(int)$fin->format('n')];

            if ($debut->format('n') === $fin->format('n')) {
                // Même mois
                return $prefixe . $jour_debut . " & " . $jour_fin . " " . $mois_fin;
            } else {
                // Mois différents - on prend le mois de la fin
                return $prefixe . $jour_debut . " & " . $jour_fin . " " . $mois_fin;
            }
        }
    }
}

// Récupère les inscriptions d'un utilisateur (version corrigée)
function getInscriptionsUtilisateur($id_utilisateur) {
    $conn = connexionBDD();

    try {
        $stmt = $conn->prepare("
            SELECT i.*, e.titre, e.description, e.date_debut, e.date_fin, e.duree_type
            FROM inscription i
            JOIN evenement e ON i.id_evenement = e.id_evenement
            WHERE i.id_utilisateur = ?
            AND i.status IN ('en attente', 'validé')
            AND e.date_fin >= CURDATE()
            ORDER BY e.date_debut ASC, i.date_inscription DESC
        ");
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la récupération des inscriptions : " . $e->getMessage());
    }
}

// Récupère les préférences de jeux pour une inscription
function getPreferencesInscription($id_inscription) {
    $conn = connexionBDD();

    try {
        $stmt = $conn->prepare("
            SELECT j.id_jeux, j.nom, j.description_courte
            FROM preferences p
            JOIN jeux j ON p.id_jeux = j.id_jeux
            WHERE p.id_inscription = ?
        ");
        $stmt->execute([$id_inscription]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la récupération des préférences : " . $e->getMessage());
    }
}

// Fonction pour désinscrire un utilisateur d'un événement
function desinscrireUtilisateur($id_inscription, $id_utilisateur) {
    $conn = connexionBDD();

    try {
        $conn->beginTransaction();

        // Vérifier que l'inscription appartient bien à l'utilisateur et qu'elle existe
        $stmt = $conn->prepare("
            SELECT i.*, e.date_debut, e.titre 
            FROM inscription i 
            JOIN evenement e ON i.id_evenement = e.id_evenement 
            WHERE i.id_inscription = ? AND i.id_utilisateur = ? 
            AND i.status IN ('en attente', 'validé')
        ");
        $stmt->execute([$id_inscription, $id_utilisateur]);
        $inscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inscription) {
            throw new Exception("Inscription non trouvée ou déjà annulée");
        }

        // Vérifier que l'événement n'est pas déjà passé
        if ($inscription['date_debut'] < date('Y-m-d')) {
            throw new Exception("Impossible de se désinscrire d'un événement passé");
        }

        // Supprimer les préférences de jeux liées à cette inscription
        $stmt_pref = $conn->prepare("DELETE FROM preferences WHERE id_inscription = ?");
        $stmt_pref->execute([$id_inscription]);

        // Supprimer l'inscription au lieu de la marquer comme annulée
        $stmt_del = $conn->prepare("DELETE FROM inscription WHERE id_inscription = ?");
        $stmt_del->execute([$id_inscription]);

        $conn->commit();

        return [
            'success' => true,
            'message' => "Vous avez été désinscrit avec succès de l'événement \"" . $inscription['titre'] . "\"."
        ];

    } catch (Exception $e) {
        $conn->rollBack();

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}