<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

/* Vérification des droits d'accès
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}
*/

// Récupérer la liste des événements
try {
    $conn = connexionBDD();
    $sql = "SELECT id_evenement, titre, date_debut, date_fin, capacite_max, duree_type 
           FROM evenement 
           ORDER BY date_debut DESC";
    $stmt = $conn->query($sql);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque événement, récupérer le nombre d'inscrits
    foreach ($evenements as &$evenement) {
        $sql_count = "SELECT COUNT(*) as nb_inscrits FROM inscription WHERE id_evenement = :id_evenement";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->execute([':id_evenement' => $evenement['id_evenement']]);
        $result = $stmt_count->fetch(PDO::FETCH_ASSOC);
        $evenement['nb_inscrits'] = $result['nb_inscrits'];
    }
    unset($evenement); // Important : détruire la référence

} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
}

// Affichage du message
$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['type']) ? $_GET['type'] : 'info';

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
            <h1>Liste des événements</h1>
            <a href="ajouter.php" class="btn btn-success">Ajouter un événement</a>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (empty($evenements)) : ?>
            <div class="alert alert-info">Aucun événement disponible.</div>
        <?php else : ?>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Titre</th>
                    <th>Dates</th>
                    <th>Durée</th>
                    <th>Capacité</th>
                    <th>Inscrits</th>
                    <th>Places restantes</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($evenements as $evenement) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($evenement['titre']); ?></td>
                        <td>
                            <?php
                            echo date('d/m/Y', strtotime($evenement['date_debut']));
                            if ($evenement['date_debut'] != $evenement['date_fin']) {
                                echo ' - ' . date('d/m/Y', strtotime($evenement['date_fin']));
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($evenement['duree_type']); ?></td>
                        <td><?php echo htmlspecialchars($evenement['capacite_max']); ?></td>
                        <td><?php echo $evenement['nb_inscrits']; ?></td>
                        <td><?php echo $evenement['capacite_max'] - $evenement['nb_inscrits']; ?></td>
                        <td>
                            <a href="modifier.php?id=<?php echo $evenement['id_evenement']; ?>" class="btn btn-sm btn-primary">Modifier</a>
                            <a href="supprimer.php?id=<?php echo $evenement['id_evenement']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');">Supprimer</a>
                            <a href="../inscriptions/liste.php?evenement=<?php echo $evenement['id_evenement']; ?>" class="btn btn-sm btn-info">Inscriptions</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>