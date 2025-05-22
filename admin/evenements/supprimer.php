<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';

/* Vérification des droits d'accès
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}
*/

// Récupérer l'ID de l'événement à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Vérifier si l'ID est valide
if ($id <= 0) {
    redirigerAvecMessage('liste.php', "ID d'événement non valide.", 'danger');
}

try {
    $pdo = connexionBDD();

    // Récupérer les informations de l'événement avant suppression
    $requete = $pdo->prepare("SELECT titre, date_debut FROM evenement WHERE id_evenement = ?");
    $requete->execute([$id]);
    $evenement = $requete->fetch(PDO::FETCH_ASSOC);

    // Si l'événement n'existe pas
    if (!$evenement) {
        redirigerAvecMessage('liste.php', "Événement non trouvé.", 'danger');
    }

    // Commencer une transaction pour assurer la cohérence
    $pdo->beginTransaction();

    // Récupération des inscriptions liées à l'événement
    $requete = $pdo->prepare("SELECT id_inscription FROM inscription WHERE id_evenement = ?");
    $requete->execute([$id]);
    $inscriptions = $requete->fetchAll(PDO::FETCH_COLUMN);

    // Suppression des préférences liées aux inscriptions
    if (!empty($inscriptions)) {
        $placeholders = implode(',', array_fill(0, count($inscriptions), '?'));
        $requete = $pdo->prepare("DELETE FROM preferences WHERE id_inscription IN ($placeholders)");
        $requete->execute($inscriptions);
    }

    // Suppression des inscriptions
    $requete = $pdo->prepare("DELETE FROM inscription WHERE id_evenement = ?");
    $requete->execute([$id]);

    // Suppression des associations jeux-événement
    $requete = $pdo->prepare("DELETE FROM jeux_evenement WHERE id_evenement = ?");
    $requete->execute([$id]);

    // Suppression de l'événement lui-même
    $requete = $pdo->prepare("DELETE FROM evenement WHERE id_evenement = ?");
    $requete->execute([$id]);

    // Valider la transaction
    $pdo->commit();

    // Rediriger avec message de succès
    redirigerAvecMessage('liste.php', "L'événement '" . $evenement['titre'] . "' a été supprimé avec succès.", 'success');

} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    redirigerAvecMessage('liste.php', "Erreur lors de la suppression : " . $e->getMessage(), 'danger');
}