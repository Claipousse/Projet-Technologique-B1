<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

// Récupérer l'ID du jeu à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Vérifier si l'ID est valide
if ($id <= 0) {
    redirigerAvecMessage('liste.php', "ID de jeu non valide.", 'danger');
}

try {
    $pdo = connexionBDD();

    // Récupérer les informations du jeu avant suppression
    $requete = $pdo->prepare("SELECT nom, image_path FROM jeux WHERE id_jeux = ?");
    $requete->execute([$id]);
    $jeu = $requete->fetch(PDO::FETCH_ASSOC);

    // Si le jeu n'existe pas
    if (!$jeu) {
        redirigerAvecMessage('liste.php', "Jeu non trouvé.", 'danger');
    }

    // Commencer une transaction pour assurer la cohérence
    $pdo->beginTransaction();

    // Suppression des ressources liées au jeu
    $requete = $pdo->prepare("DELETE FROM ressource WHERE id_jeux = ?");
    $requete->execute([$id]);

    // Suppression des préférences liées au jeu
    $requete = $pdo->prepare("DELETE FROM preferences WHERE id_jeux = ?");
    $requete->execute([$id]);

    // Suppression du jeu des événements
    $requete = $pdo->prepare("DELETE FROM jeux_evenement WHERE id_jeux = ?");
    $requete->execute([$id]);

    // Suppression du jeu lui-même
    $requete = $pdo->prepare("DELETE FROM jeux WHERE id_jeux = ?");
    $requete->execute([$id]);

    // Supprimer l'image physique si elle existe
    if ($jeu['image_path'] && file_exists(__DIR__ . '/../../' . $jeu['image_path'])) {
        unlink(__DIR__ . '/../../' . $jeu['image_path']);
    }

    // Valider la transaction
    $pdo->commit();

    // Rediriger avec message de succès
    redirigerAvecMessage('liste.php', $jeu['nom'] . " a bien été supprimé !", 'success');

} catch (PDOException $e) {
    // En cas d'erreur, annuler la transaction
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    redirigerAvecMessage('liste.php', "Erreur lors de la suppression : " . $e->getMessage(), 'danger');
}