<?php
require_once __DIR__ . "/../../config/config.php";
session_start(); // On lance la session pour savoir qui est connecté
$pdo = connexionBDD();

// Si la personne n'est pas connectée, on la redirige vers la page de connexion
if (!isset($_SESSION['email'])) {
    header("Location: ../connexion.php");
    exit();
}

// On récupère les infos envoyées depuis le formulaire (POST)
$nom = htmlspecialchars($_POST['nom']);
$prenom = htmlspecialchars($_POST['prenom']);
$email = htmlspecialchars($_POST['email']);
$nb_accompagnant = intval($_POST['nb_accompagnant']);
$id_evenement = intval($_POST['id_evenement']);
$date_inscription = date("Y-m-d");

// On vérifie que les champs importants ne sont pas vides
if (empty($nom) || empty($prenom) || empty($email) || empty($id_evenement)) {
    echo "Veuillez remplir tous les champs obligatoires.";
    exit();
}

// On récupère la capacité max de l'événement
$sqlCap = "SELECT capacite_max FROM evenement WHERE id_evenement = ?";
$stmtCap = $pdo->prepare($sqlCap);
$stmtCap->execute([$id_evenement]);
$capacite = $stmtCap->fetchColumn();

// On calcule combien de personnes sont déjà inscrites (participants + accompagnants)
$sqlTotal = "SELECT SUM(1 + nb_accompagnant) FROM inscription WHERE id_evenement = ?";
$stmtTotal = $pdo->prepare($sqlTotal);
$stmtTotal->execute([$id_evenement]);
$nb_deja_inscrits = $stmtTotal->fetchColumn();

// On calcule combien de places cette nouvelle inscription va prendre
$place_requise = 1 + $nb_accompagnant;

// Si ça dépasse la capacité, on bloque l'inscription
if ($nb_deja_inscrits + $place_requise > $capacite) {
    echo "Désolé, il n'y a plus assez de places disponibles pour cet événement.";
    exit();
}

// Tout est bon, on enregistre l'inscription directement comme validée
$sql = "INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status)
        VALUES ((SELECT id_utilisateur FROM utilisateur WHERE email = ?), ?, ?, ?, 'validée')";

$stmt = $pdo->prepare($sql);
$stmt->execute([$email, $id_evenement, $nb_accompagnant, $date_inscription]);

// On envoie un mail de confirmation à l'utilisateur
$to = $email;
$subject = "Confirmation d'inscription à l'événement";
$message = "Bonjour $prenom $nom,\n\nVotre inscription à l'événement est bien enregistrée.\n\nMerci et à bientôt sur Pistache !";
$headers = "From: pistache@local.test";

mail($to, $subject, $message, $headers);

// On redirige vers la page de confirmation
header("Location: confirmation.html");
exit();
?>
