<?php
/* Fichier pour suppression d'un utilisateur*/

// Inclusion du fichier de configuration
require_once __DIR__ . '/../../config/config.php';

// Vérification de la connexion de l'utilisateur
if (!estConnecte() || !estAdmin()) {
    // Rediriger vers la page de connexion 
    rediriger('connexion.php');
}

// Récupérer l'ID de l'utilisateur à supprimer
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

// Vérifier si l'ID est valide
if ($id <= 0) {
    echo "Erreur: ID d'utilisateur non valide.";
    exit;
}

// Connexion à la base de données
$pdo = connexionBDD();

// Récupérer les informations de l'utilisateur à patir de l'id 
$requete = $pdo->prepare("SELECT nom, prenom, email, role FROM utilisateur WHERE id_utilisateur = ?");
$requete->execute([$id]);
$utilisateur = $requete->fetch(PDO::FETCH_ASSOC);

// Si l'utilisateur n'existe pas
if (!$utilisateur) {
    echo "Erreur: Utilisateur non trouvé.";
    exit;
}

// Empêcher la suppression du compte admin (sécurité supplémentaire)
if ($id == $_SESSION['user_id']) {
    echo "Erreur: Vous ne pouvez pas supprimer votre propre compte.";
    exit;
}

// suppression
if (isset($_GET['confirmer']) && $_GET['confirmer'] == 1) {
    try {
        // Commencer une transaction
        $pdo->beginTransaction();
        
        //Récupération de toutes les inscriptions de l'utilisateur
        $requete = $pdo->prepare("SELECT id_inscription FROM inscription WHERE id_utilisateur = ?");
        $requete->execute([$id]);
        $inscriptions = $requete->fetchAll(PDO::FETCH_COLUMN);
        
        //Suppression préférences
        if (!empty($inscriptions)) {
            $placeholders = implode(',', array_fill(0, count($inscriptions), '?'));
            $requete = $pdo->prepare("DELETE FROM preferences WHERE id_inscription IN ($placeholders)");
            $requete->execute($inscriptions);
        }
        
        //Suppression inscriptions
        $requete = $pdo->prepare("DELETE FROM inscription WHERE id_utilisateur = ?");
        $requete->execute([$id]);
        
        //suppression utilisateur
        $requete = $pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
        $requete->execute([$id]);
        
        // Valider la transaction
        $pdo->commit();
        
        // Rediriger vers la liste des utilisateurs avec un message de succès
        header("Location: liste.php?message=" . urlencode("L'utilisateur a été supprimé avec succès."));
        exit;
        
    } catch (PDOException $e) {
        // En cas d'erreur, annulation
        $pdo->rollBack();
        $message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>