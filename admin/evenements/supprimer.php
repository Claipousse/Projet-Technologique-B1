<?php
/* Fichier suppression evenement*/

// Inclusion du fichier de configuration
require_once __DIR__ . '/../../config/config.php';

/* Vérification des droits d'accès
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}
*/

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

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <h1>Supprimer un événement</h1>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="alert alert-warning">
            <h4>Attention !</h4>
            <p>Vous êtes sur le point de supprimer l'événement suivant :</p>
            <ul>
                <li><strong>Titre :</strong> <?php echo htmlspecialchars($evenement['titre']); ?></li>
                <li><strong>Date de début :</strong> <?php echo date('d/m/Y', strtotime($evenement['date_debut'])); ?></li>
            </ul>
            <p><strong>Cette action est irréversible et supprimera également toutes les inscriptions associées à cet événement.</strong></p>
        </div>

        <div class="mt-4">
            <a href="supprimer.php?id=<?php echo $id; ?>&confirmer=1" class="btn btn-danger" onclick="return confirm('Êtes-vous vraiment sûr de vouloir supprimer cet événement ? Cette action ne peut pas être annulée.');">
                Confirmer la suppression
            </a>
            <a href="liste.php" class="btn btn-secondary">
                Annuler
            </a>
        </div>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>