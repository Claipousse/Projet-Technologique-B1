<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions.php';

// Récupérer les statistiques pour le tableau de bord
try {
    $pdo = connexionBDD();
    
    // Nombre total de jeux
    $stmt = $pdo->query("SELECT COUNT(*) FROM jeux");
    $nbJeux = $stmt->fetchColumn();
    
    // Nombre total d'événements
    $stmt = $pdo->query("SELECT COUNT(*) FROM evenement");
    $nbEvenements = $stmt->fetchColumn();
    
    // Nombre d'événements à venir
    $stmt = $pdo->query("SELECT COUNT(*) FROM evenement WHERE date_debut >= CURDATE()");
    $nbEvenementsAVenir = $stmt->fetchColumn();
    
    // Nombre total d'inscriptions
    $stmt = $pdo->query("SELECT COUNT(*) FROM inscription");
    $nbInscriptions = $stmt->fetchColumn();
    
    // Nombre d'inscriptions en attente
    $stmt = $pdo->query("SELECT COUNT(*) FROM inscription WHERE status = 'en attente'");
    $nbInscriptionsEnAttente = $stmt->fetchColumn();
    
    // Récupérer les 5 prochains événements
    $stmt = $pdo->query("SELECT id_evenement, titre, date_debut, date_fin, capacite_max FROM evenement 
                        WHERE date_debut >= CURDATE() 
                        ORDER BY date_debut ASC LIMIT 5");
    $prochainsEvenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Pour chaque événement, récupérer le nombre d'inscrits
    foreach ($prochainsEvenements as &$evenement) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM inscription WHERE id_evenement = ?");
        $stmt->execute([$evenement['id_evenement']]);
        $evenement['nb_inscrits'] = $stmt->fetchColumn();
    }
    
    // Récupérer les 5 dernières inscriptions
    $stmt = $pdo->query("SELECT i.id_inscription, i.date_inscription, i.status, 
                           u.nom, u.prenom, e.titre 
                        FROM inscription i
                        JOIN utilisateur u ON i.id_utilisateur = u.id_utilisateur
                        JOIN evenement e ON i.id_evenement = e.id_evenement
                        ORDER BY i.date_inscription DESC LIMIT 5");
    $dernieresInscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
}

include_once 'includes/admin-header.php';
?>

<div class="container mt-4">
    <h1>Tableau de bord</h1>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-danger"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="row mt-4">
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Jeux</h5>
                    <p class="card-text display-4"><?php echo $nbJeux; ?></p>
                </div>
                <div class="card-footer d-flex">
                    <a href="jeux/liste.php" class="text-white text-decoration-none">Voir détails</a>
                    <span class="ms-auto">
                        <i class="bi bi-dice-6"></i>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Événements</h5>
                    <p class="card-text display-4"><?php echo $nbEvenements; ?></p>
                </div>
                <div class="card-footer d-flex">
                    <a href="evenements/liste.php" class="text-white text-decoration-none">Voir détails</a>
                    <span class="ms-auto">
                        <i class="bi bi-calendar-event"></i>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <h5 class="card-title">Événements à venir</h5>
                    <p class="card-text display-4"><?php echo $nbEvenementsAVenir; ?></p>
                </div>
                <div class="card-footer d-flex">
                    <a href="evenements/liste.php" class="text-dark text-decoration-none">Voir détails</a>
                    <span class="ms-auto">
                        <i class="bi bi-calendar-plus"></i>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <h5 class="card-title">Inscriptions en attente</h5>
                    <p class="card-text display-4"><?php echo $nbInscriptionsEnAttente; ?></p>
                </div>
                <div class="card-footer d-flex">
                    <a href="inscriptions/liste.php" class="text-white text-decoration-none">Voir détails</a>
                    <span class="ms-auto">
                        <i class="bi bi-people"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Prochains événements -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Prochains événements</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($prochainsEvenements)): ?>
                        <p class="text-muted">Aucun événement à venir</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Événement</th>
                                    <th>Date</th>
                                    <th>Inscriptions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($prochainsEvenements as $evenement): ?>
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
                                        <td>
                                            <?php echo $evenement['nb_inscrits']; ?> / <?php echo $evenement['capacite_max']; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="evenements/liste.php" class="btn btn-sm btn-outline-primary">Voir tous les événements</a>
                </div>
            </div>
        </div>
        
        <!-- Dernières inscriptions -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Dernières inscriptions</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($dernieresInscriptions)): ?>
                        <p class="text-muted">Aucune inscription récente</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Participant</th>
                                    <th>Événement</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dernieresInscriptions as $inscription): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?></td>
                                        <td><?php echo htmlspecialchars($inscription['titre']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($inscription['date_inscription'])); ?></td>
                                        <td>
                                            <?php if ($inscription['status'] == 'en attente'): ?>
                                                <span class="badge bg-warning">En attente</span>
                                            <?php elseif ($inscription['status'] == 'validé'): ?>
                                                <span class="badge bg-success">Validé</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Annulé</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="inscriptions/liste.php" class="btn btn-sm btn-outline-primary">Voir toutes les inscriptions</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/admin-footer.php'; ?>