<?php
/* Fichier suppression evenement*/

// Inclusion du fichier de configuration
require_once __DIR__ . '/../../config/config.php';

// Vérification connexion
if (!estConnecte() || !estAdmin()) {
    // Rediriger vers la page de connexion 
    rediriger('connexion.php');
}

// Récupérer l'ID de l'événement à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

// Vérifier si l'ID est valide
if ($id <= 0) {
    echo "Erreur: ID d'événement non valide.";
    exit;
}

// Connexion à la base de données
$pdo = connexionBDD();

// Récupération informations événement
$requete = $pdo->prepare("SELECT titre, date_debut FROM evenement WHERE id_evenement = ?");
$requete->execute([$id]);
$evenement = $requete->fetch(PDO::FETCH_ASSOC);

// Si l'événement n'existe pas
if (!$evenement) {
    echo "Erreur: Événement non trouvé.";
    exit;
}

// suppression
if (isset($_GET['confirmer']) && $_GET['confirmer'] == 1) {
    try {
        // Commencer une transaction 
        $pdo->beginTransaction();
        
        // Récupéreration inscriptions 
        $requete = $pdo->prepare("SELECT id_inscription FROM inscription WHERE id_evenement = ?");
        $requete->execute([$id]);
        $inscriptions = $requete->fetchAll(PDO::FETCH_COLUMN);
        
        // Suppression préférences
        if (!empty($inscriptions)) {
            $placeholders = implode(',', array_fill(0, count($inscriptions), '?'));
            $requete = $pdo->prepare("DELETE FROM preferences WHERE id_inscription IN ($placeholders)");
            $requete->execute($inscriptions);
        }
        
        // Suppression inscriptions
        $requete = $pdo->prepare("DELETE FROM inscription WHERE id_evenement = ?");
        $requete->execute([$id]);
        
        // Suppression associations jeux
        $requete = $pdo->prepare("DELETE FROM jeux_evenement WHERE id_evenement = ?");
        $requete->execute([$id]);
        
        // suppression événement
        $requete = $pdo->prepare("DELETE FROM evenement WHERE id_evenement = ?");
        $requete->execute([$id]);
        
        // Validation
        $pdo->commit();
        
        // Rediriger vers la liste des événements avec un message de succès
        header("Location: liste.php?message=" . urlencode("L'événement a été supprimé avec succès."));
        exit;
        
    } catch (PDOException $e) {
        // En cas d'erreur, annulation
        $pdo->rollBack();
        $message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

?>