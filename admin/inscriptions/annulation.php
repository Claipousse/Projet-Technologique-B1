<?php
/*Fichier annulation inscription*/

// Inclusion du fichier de configuration
require_once __DIR__ . '/../../config/config.php';

// Vérification connexion
if (!estConnecte() || !estAdmin()) {
    // Rediriger vers la page de connexion
    rediriger('connexion.php');
}

// Récupérer l'ID de l'inscription à annuler
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

// Vérifier si l'ID est valide
if ($id <= 0) {
    echo "Erreur: ID d'inscription non valide.";
    exit;
}

// Connexion à la base de données
$pdo = connexionBDD();

// Récupérer les informations de l'inscription
$requete = $pdo->prepare("
    SELECT i.id_inscription, i.nb_accompagnant, i.date_inscription, i.status,
           u.nom, u.prenom, u.email,
           e.titre, e.date_debut
    FROM inscription i
    JOIN utilisateur u ON i.id_utilisateur = u.id_utilisateur
    JOIN evenement e ON i.id_evenement = e.id_evenement
    WHERE i.id_inscription = ?
");
$requete->execute([$id]);
$inscription = $requete->fetch(PDO::FETCH_ASSOC);

// l'inscription n'existe pas
if (!$inscription) {
    echo "Erreur: Inscription non trouvée.";
    exit;
}

// suppression
if (isset($_GET['confirmer']) && $_GET['confirmer'] == 1) {
    try {
        // Commencer une transaction
        $pdo->beginTransaction();
        
        //Supprimer les préférences 
        $requete = $pdo->prepare("DELETE FROM preferences WHERE id_inscription = ?");
        $requete->execute([$id]);
        
        //Suppression inscription
        $requete = $pdo->prepare("DELETE FROM inscription WHERE id_inscription = ?");
        $requete->execute([$id]);
        
        // Validation
        $pdo->commit();
        
        // Rediriger vers la liste des inscriptions avec un message de succès
        header("Location: liste.php?message=" . urlencode("L'inscription a été supprimée avec succès."));
        exit;
        
    } catch (PDOException $e) {
        // En cas d'erreur, annulation
        $pdo->rollBack();
        $message = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

?>

<?php
// annulation rapide d'une inscription par l'admin

// Si un ID est passé et qu'on n'a pas cliqué sur "confirmer", on peut juste changer son statut en "Annulée"
// C’est utile si on veut juste annuler l'inscription sans supprimer les données
if (isset($_GET['id']) && !isset($_GET['confirmer'])) {
    $id = intval($_GET['id']);

    // On prépare la requête pour changer le statut
    $changerStatut = $pdo->prepare("UPDATE inscription SET status = 'annulée' WHERE id_inscription = ?");
    $changerStatut->execute([$id]);

    // On redirige vers la liste avec un petit message (on peut adapter si besoin)
    header("Location: liste.php?message=" . urlencode("L'inscription a été annulée (statut modifié)."));
    exit;
}
?>

