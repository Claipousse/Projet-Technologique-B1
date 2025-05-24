<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

// Vérifier qu'un ID d'inscription a été fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    rediriger('liste.php');
}

$id_inscription = (int)$_GET['id'];

try {
    $pdo = connexionBDD();

    // Vérifier que l'inscription existe et est en attente
    $stmt = $pdo->prepare("
        SELECT i.*, e.titre as evenement_titre, u.prenom, u.nom
        FROM inscription i
        JOIN evenement e ON i.id_evenement = e.id_evenement
        JOIN utilisateur u ON i.id_utilisateur = u.id_utilisateur
        WHERE i.id_inscription = ?
    ");
    $stmt->execute([$id_inscription]);
    $inscription = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inscription) {
        rediriger('liste.php');
    }

    if ($inscription['status'] !== 'en attente') {
        rediriger('liste.php');
    }

    // Refuser l'inscription
    $stmt = $pdo->prepare("UPDATE inscription SET status = 'annulé' WHERE id_inscription = ?");
    $stmt->execute([$id_inscription]);

    // Redirection simple sans message
    rediriger('liste.php');

} catch (PDOException $e) {
    rediriger('liste.php');
}