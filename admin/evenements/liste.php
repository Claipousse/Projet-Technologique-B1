<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

// Récupérer la liste des événements
try {
    $conn = connexionBDD();
    $sql = "SELECT e.id_evenement, e.titre, e.date_debut, e.date_fin, e.capacite_max, e.duree_type,
                   COUNT(i.id_inscription) as nb_inscrits
            FROM evenement e
            LEFT JOIN inscription i ON e.id_evenement = i.id_evenement
            GROUP BY e.id_evenement
            ORDER BY e.date_debut DESC";
    $stmt = $conn->query($sql);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $evenements = [];
    $message = "Erreur: " . $e->getMessage();
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['type']) ? $_GET['type'] : 'info';

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Liste des événements</h1>
            <a href="ajouter.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ajouter un événement
            </a>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($evenements)) : ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Aucun événement disponible.
            </div>
        <?php else : ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                        <tr>
                            <th class="fw-bold text-muted">TITRE</th>
                            <th class="fw-bold text-muted">DATES</th>
                            <th class="fw-bold text-muted">DURÉE</th>
                            <th class="fw-bold text-muted text-center">PARTICIPATION</th>
                            <th class="fw-bold text-muted text-center">ACTIONS</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($evenements as $evenement) : ?>
                            <?php
                            $pourcentage = ($evenement['capacite_max'] > 0) ?
                                round(($evenement['nb_inscrits'] / $evenement['capacite_max']) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($evenement['titre']); ?></strong>
                                </td>
                                <td>
                                    <?php echo formaterDateEvenement($evenement['date_debut'], $evenement['date_fin']); ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($evenement['duree_type']); ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="progress-bar-custom">
                                        <div class="progress-fill" style="width: <?php echo $pourcentage; ?>%"></div>
                                        <div class="progress-text">
                                            <?php echo $evenement['nb_inscrits']; ?>/<?php echo $evenement['capacite_max']; ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="modifier.php?id=<?php echo $evenement['id_evenement']; ?>"
                                       class="btn btn-outline-primary btn-sm"
                                       style="margin-right: 4px;"
                                       title="Modifier">
                                        <i class="bi bi-pencil"></i> Modifier
                                    </a>
                                    <a href="supprimer.php?id=<?php echo $evenement['id_evenement']; ?>"
                                       class="btn btn-outline-danger btn-sm"
                                       style="margin-right: 4px;"
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet événement ?');"
                                       title="Supprimer">
                                        <i class="bi bi-trash"></i> Supprimer
                                    </a>
                                    <a href="../inscriptions/liste.php?evenement=<?php echo $evenement['id_evenement']; ?>"
                                       class="btn btn-outline-info btn-sm" title="Voir les inscriptions">
                                        <i class="bi bi-people"></i> Inscriptions
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>