<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

// Traitement des messages
$message = '';
$typeMessage = 'info';

if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $typeMessage = isset($_GET['type']) ? $_GET['type'] : 'info';
}

try {
    $pdo = connexionBDD();

    // Récupérer toutes les inscriptions avec les informations associées
    $sql = "SELECT 
                i.id_inscription,
                i.nb_accompagnant,
                i.date_inscription,
                i.status,
                u.nom,
                u.prenom,
                u.email,
                e.titre as evenement_titre,
                e.date_debut,
                e.date_fin,
                e.capacite_max,
                e.id_evenement
            FROM inscription i
            JOIN utilisateur u ON i.id_utilisateur = u.id_utilisateur
            JOIN evenement e ON i.id_evenement = e.id_evenement
            ORDER BY 
                CASE i.status 
                    WHEN 'en attente' THEN 1 
                    WHEN 'validé' THEN 2 
                    WHEN 'annulé' THEN 3 
                END,
                i.date_inscription DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque inscription, récupérer les préférences de jeux
    foreach ($inscriptions as &$inscription) {
        $stmt = $pdo->prepare("
            SELECT j.nom 
            FROM preferences p 
            JOIN jeux j ON p.id_jeux = j.id_jeux 
            WHERE p.id_inscription = ?
        ");
        $stmt->execute([$inscription['id_inscription']]);
        $preferences = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $inscription['preferences'] = $preferences;

        // Calculer le nombre d'inscrits pour cet événement
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscription WHERE id_evenement = ? AND status = 'validé'");
        $stmt->execute([$inscription['id_evenement']]);
        $inscription['nb_inscrits_valides'] = $stmt->fetchColumn();
    }

    // Séparer les inscriptions par statut
    $inscriptionsEnAttente = array_filter($inscriptions, function($i) { return $i['status'] === 'en attente'; });
    $inscriptionsValidees = array_filter($inscriptions, function($i) { return $i['status'] === 'validé'; });
    $inscriptionsAnnulees = array_filter($inscriptions, function($i) { return $i['status'] === 'annulé'; });

} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des inscriptions : " . $e->getMessage();
    $typeMessage = 'danger';
}

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-people"></i> Gestion des inscriptions</h1>
        </div>

        <?php if (!empty($message)): ?>
            <?php echo alerte($message, $typeMessage); ?>
        <?php endif; ?>

        <!-- Inscriptions en attente -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-clock text-warning"></i> Inscriptions en attente (<?php echo count($inscriptionsEnAttente); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($inscriptionsEnAttente)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                            <tr>
                                <th>Participant</th>
                                <th>Événement</th>
                                <th>Date inscription</th>
                                <th>Accompagnants</th>
                                <th>Préférences</th>
                                <th>Capacité</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($inscriptionsEnAttente as $inscription): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($inscription['email']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($inscription['evenement_titre']); ?></strong><br>
                                        <small class="text-muted">
                                            <?php echo formaterDateEvenement($inscription['date_debut'], $inscription['date_fin'], true); ?>
                                        </small>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($inscription['date_inscription'])); ?></td>
                                    <td class="text-center">
                                        <?php echo $inscription['nb_accompagnant'] ?: 0; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($inscription['preferences'])): ?>
                                            <small>
                                                <?php echo implode(', ', array_map('htmlspecialchars', $inscription['preferences'])); ?>
                                            </small>
                                        <?php else: ?>
                                            <small class="text-muted">Aucune préférence</small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                        $totalPlaces = $inscription['nb_accompagnant'] + 1; // +1 pour le participant principal
                                        $placesRestantes = $inscription['capacite_max'] - $inscription['nb_inscrits_valides'];
                                        ?>
                                        <small>
                                            <?php echo $inscription['nb_inscrits_valides']; ?>/<?php echo $inscription['capacite_max']; ?><br>
                                            <?php if ($placesRestantes >= $totalPlaces): ?>
                                                <span class="text-success">✓ Places disponibles</span>
                                            <?php else: ?>
                                                <span class="text-danger">⚠ Pas assez de places</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="valider.php?id=<?php echo $inscription['id_inscription']; ?>"
                                               class="btn btn-success btn-sm"
                                               onclick="return confirm('Valider cette inscription ?')">
                                                <i class="bi bi-check-circle"></i> Valider
                                            </a>
                                            <a href="annuler.php?id=<?php echo $inscription['id_inscription']; ?>"
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Refuser cette inscription ?')">
                                                <i class="bi bi-x-circle"></i> Refuser
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p class="text-muted">Aucune inscription en attente</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Onglets pour les autres inscriptions -->
        <ul class="nav nav-tabs" id="inscriptionTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="validees-tab" data-bs-toggle="tab" data-bs-target="#validees" type="button" role="tab">
                    <i class="bi bi-check-circle text-success"></i> Validées (<?php echo count($inscriptionsValidees); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="annulees-tab" data-bs-toggle="tab" data-bs-target="#annulees" type="button" role="tab">
                    <i class="bi bi-x-circle text-danger"></i> Annulées (<?php echo count($inscriptionsAnnulees); ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="inscriptionTabsContent">
            <!-- Inscriptions validées -->
            <div class="tab-pane fade show active" id="validees" role="tabpanel">
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (!empty($inscriptionsValidees)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Participant</th>
                                        <th>Événement</th>
                                        <th>Date inscription</th>
                                        <th>Accompagnants</th>
                                        <th>Préférences</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($inscriptionsValidees as $inscription): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($inscription['email']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($inscription['evenement_titre']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo formaterDateEvenement($inscription['date_debut'], $inscription['date_fin'], true); ?>
                                                </small>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($inscription['date_inscription'])); ?></td>
                                            <td class="text-center"><?php echo $inscription['nb_accompagnant'] ?: 0; ?></td>
                                            <td>
                                                <?php if (!empty($inscription['preferences'])): ?>
                                                    <small><?php echo implode(', ', array_map('htmlspecialchars', $inscription['preferences'])); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Aucune préférence</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted">Aucune inscription validée</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Inscriptions annulées -->
            <div class="tab-pane fade" id="annulees" role="tabpanel">
                <div class="card">
                    <div class="card-body p-0">
                        <?php if (!empty($inscriptionsAnnulees)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                    <tr>
                                        <th>Participant</th>
                                        <th>Événement</th>
                                        <th>Date inscription</th>
                                        <th>Accompagnants</th>
                                        <th>Préférences</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($inscriptionsAnnulees as $inscription): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($inscription['email']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($inscription['evenement_titre']); ?></strong><br>
                                                <small class="text-muted">
                                                    <?php echo formaterDateEvenement($inscription['date_debut'], $inscription['date_fin'], true); ?>
                                                </small>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($inscription['date_inscription'])); ?></td>
                                            <td class="text-center"><?php echo $inscription['nb_accompagnant'] ?: 0; ?></td>
                                            <td>
                                                <?php if (!empty($inscription['preferences'])): ?>
                                                    <small><?php echo implode(', ', array_map('htmlspecialchars', $inscription['preferences'])); ?></small>
                                                <?php else: ?>
                                                    <small class="text-muted">Aucune préférence</small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-muted">Aucune inscription annulée</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>