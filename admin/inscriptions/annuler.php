<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

// Vérifier qu'un ID d'inscription a été fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirigerAvecMessage('liste.php', 'ID d\'inscription manquant.', 'danger');
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
        redirigerAvecMessage('liste.php', 'Inscription introuvable.', 'danger');
    }

    if ($inscription['status'] !== 'en attente') {
        redirigerAvecMessage('liste.php', 'Cette inscription n\'est pas en attente.', 'warning');
    }

    // Refuser l'inscription
    $stmt = $pdo->prepare("UPDATE inscription SET status = 'annulé' WHERE id_inscription = ?");
    $stmt->execute([$id_inscription]);

    if ($stmt->rowCount() > 0) {
        $message = 'Inscription de ' . htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']) .
            ' pour "' . htmlspecialchars($inscription['evenement_titre']) . '" refusée.';
        redirigerAvecMessage('liste.php', $message, 'warning');
    } else {
        redirigerAvecMessage('liste.php', 'Erreur lors du refus de l\'inscription.', 'danger');
    }

} catch (PDOException $e) {
    redirigerAvecMessage('liste.php', 'Erreur base de données : ' . $e->getMessage(), 'danger');
}