<?php
/* Fichier pour suppression d'un jeu */

// Inclusion du fichier de configuration
require_once __DIR__ . '/../../config/config.php';

/* Vérification de la connexion de l'utilisateur
if (!estConnecte() || !estAdmin()) {
    // Rediriger vers la page de connexion 
    rediriger('connexion.php');
}
*/

// Récupérer l'ID du jeu à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

// Vérifier si l'ID est valide
if ($id <= 0) {
    echo "Erreur: ID de jeu non valide.";
    exit;
}

// Connexion à la base de données
$pdo = connexionBDD();

// Récupérer le nom  du jeu à partir de l'id
$requete = $pdo->prepare("SELECT nom FROM jeux WHERE id_jeux = ?");
$requete->execute([$id]);
$jeu = $requete->fetch(PDO::FETCH_ASSOC);

// Si le jeu n'existe pas
if (!$jeu) {
    echo "Erreur: Jeu non trouvé.";
    exit;
}

// suppression
if (isset($_GET['confirmer']) && $_GET['confirmer'] == 1) {
    try {
        // Commencer une transaction 
        $pdo->beginTransaction();
        
        // Suppression ressources jeu
        $requete = $pdo->prepare("DELETE FROM ressource WHERE id_jeux = ?");
        $requete->execute([$id]);
        
        // Suppression preferences jeu
        $requete = $pdo->prepare("DELETE FROM preferences WHERE id_jeux = ?");
        $requete->execute([$id]);
        
        // Suppresion jeu de événements 
        $requete = $pdo->prepare("DELETE FROM jeux_evenement WHERE id_jeux = ?");
        $requete->execute([$id]);
        
        //suppression jeu 
        $requete = $pdo->prepare("DELETE FROM jeux WHERE id_jeux = ?");
        $requete->execute([$id]);
        
        // Validation
        $pdo->commit();
        
        // Rediriger vers la liste des jeux avec un message de succès
        header("Location: liste.php?message=" . urlencode("Le jeu a été supprimé avec succès."));
        exit;
        
    } catch (PDOException $e) {
        // En cas d'erreur, annulation
        $pdo->rollBack();
        $message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>