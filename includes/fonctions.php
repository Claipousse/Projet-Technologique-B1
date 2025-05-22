<?php

/*Ce fichier sert à répertorier de nombreuses fonctions que nous appellerons dans les autres fichiers .php, comme
la plupart des fonctions seront utilisés plusieurs fois, s'il y a un problème avec l'une d'elle, il suffira de modifier
ce fichier pour patcher le problème dans tous les autres fichiers, au lieu de modifier chaques fichiers où la fonction
est présente.*/

require_once __DIR__ . "/../config/config.php";

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

//Obtenir l'URL actuelle
function urlActuelle() {
    /*On vérifie d'abord le protocole utilisé avec une condition ternaire (https ou http),
    puis on concatène la string avec le reste de l'url */
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
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

//Savoir si un événement existe à une date donnée
function evenementExiste($date_debut, $date_fin, $id = null) /*id = paramètre optionnel permettant exclure un événement de la recherche */{
    $conn = connexionBDD();
    $sql = "SELECT id_evenement FROM evenement WHERE 
            (date_debut BETWEEN :date_debut AND :date_fin) OR --La date de début d'un événement existant tombe entre les dates fournies
            (date_fin BETWEEN :date_debut AND :date_fin) OR --La date de fin d'un événement existant tombe entre les dates fournies
            (:date_debut BETWEEN date_debut AND date_fin) OR --La date de début fournie tombe pendant un événement existant
            (:date_fin BETWEEN date_debut AND date_fin) -- La date de fin fournie tombe pendant un événement existant";

    /*Si id fourni, requête ajoute une condition pour exclure l'événement avec cet ID
    utile lors de la mise à jour d'un événement existant pour ne pas le considérer comme en conflit avec lui-même*/
    if ($id !== null) {
        $sql .= " AND id_evenement != :id";
        $params[':id'] = $id;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount() > 0; //Si au moins 1 événement à été trouvé true, sinon false
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