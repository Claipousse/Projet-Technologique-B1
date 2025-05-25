<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../connexion.php');
}

// Récupérer les statistiques pour le tableau de bord
try {
    $pdo = connexionBDD();

    // Nombre total de jeux
    $stmt = $pdo->query("SELECT COUNT(*) FROM jeux");
    $nbJeux = $stmt->fetchColumn();

    // Nombre total d'événements
    $stmt = $pdo->query("SELECT COUNT(*) FROM evenement");
    $nbEvenements = $stmt->fetchColumn();

    // Nombre d'inscriptions en attente
    $stmt = $pdo->query("SELECT COUNT(*) FROM inscription WHERE status = 'en attente'");
    $nbInscriptionsEnAttente = $stmt->fetchColumn();

    // Récupérer les 5 derniers jeux ajoutés (modifié de 3 à 5)
    $stmt = $pdo->query("SELECT id_jeux, nom, date_ajout FROM jeux 
                        ORDER BY date_ajout DESC, id_jeux DESC LIMIT 5");
    $derniersJeux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupérer les 3 derniers événements
    $stmt = $pdo->query("SELECT id_evenement, titre, date_debut, date_fin, capacite_max FROM evenement 
                        ORDER BY date_debut DESC LIMIT 3");
    $derniersEvenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque événement, récupérer le nombre d'inscrits (avec accompagnants)
    foreach ($derniersEvenements as &$evenement) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(1 + COALESCE(nb_accompagnant, 0)), 0) FROM inscription WHERE id_evenement = ? AND status = 'validé'");
        $stmt->execute([$evenement['id_evenement']]);
        $evenement['nb_inscrits'] = $stmt->fetchColumn();
    }

    // Récupérer les 3 dernières inscriptions
    $stmt = $pdo->query("SELECT i.id_inscription, i.date_inscription, i.status, 
                           u.nom, u.prenom, e.titre 
                        FROM inscription i
                        JOIN utilisateur u ON i.id_utilisateur = u.id_utilisateur
                        JOIN evenement e ON i.id_evenement = e.id_evenement
                        ORDER BY i.date_inscription DESC LIMIT 3");
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
            <div class="col-md-4 mb-4">
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

            <div class="col-md-4 mb-4">
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

            <div class="col-md-4 mb-4">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body">
                        <h5 class="card-title">Inscriptions en attente</h5>
                        <p class="card-text display-4"><?php echo $nbInscriptionsEnAttente; ?></p>
                    </div>
                    <div class="card-footer d-flex">
                        <a href="inscriptions/liste.php" class="text-dark text-decoration-none">Voir détails</a>
                        <span class="ms-auto">
                        <i class="bi bi-people"></i>
                    </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listes des derniers éléments -->
        <div class="row dashboard-section">
            <!-- Derniers jeux ajoutés -->
            <div class="col-md-4">
                <div class="card dashboard-cards dashboard-uniform-height">
                    <div class="card-header">
                        <h5>5 derniers jeux ajoutés</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($derniersJeux)): ?>
                            <p class="text-muted mb-0">Aucun jeu disponible</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($derniersJeux as $jeu): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between align-items-center">
                                            <h6 class="mb-0 me-2"><?php echo htmlspecialchars($jeu['nom']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $jeu['date_ajout'] ? date('d/m/Y', strtotime($jeu['date_ajout'])) : 'N/A'; ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="jeux/liste.php" class="btn btn-sm btn-outline-primary">Voir tous les jeux</a>
                    </div>
                </div>
            </div>

            <!-- Derniers événements -->
            <div class="col-md-4">
                <div class="card dashboard-cards dashboard-uniform-height">
                    <div class="card-header">
                        <h5>Derniers événements</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($derniersEvenements)): ?>
                            <p class="text-muted mb-0">Aucun événement disponible</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($derniersEvenements as $evenement): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="me-2">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($evenement['titre']); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo $evenement['nb_inscrits']; ?> / <?php echo $evenement['capacite_max']; ?> inscrits
                                                </small>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($evenement['date_debut'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="evenements/liste.php" class="btn btn-sm btn-outline-primary">Voir tous les événements</a>
                    </div>
                </div>
            </div>

            <!-- Dernières inscriptions -->
            <div class="col-md-4">
                <div class="card dashboard-cards dashboard-uniform-height">
                    <div class="card-header">
                        <h5>Dernières inscriptions</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($dernieresInscriptions)): ?>
                            <p class="text-muted mb-0">Aucune inscription récente</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($dernieresInscriptions as $inscription): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between align-items-start">
                                            <div class="me-2">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($inscription['prenom'] . ' ' . $inscription['nom']); ?></h6>
                                                <small class="text-muted d-block"><?php echo htmlspecialchars($inscription['titre']); ?></small>
                                                <div class="mt-1">
                                                    <?php if ($inscription['status'] == 'en attente'): ?>
                                                        <span class="badge bg-warning text-dark">En attente</span>
                                                    <?php elseif ($inscription['status'] == 'validé'): ?>
                                                        <span class="badge bg-success">Validé</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Annulé</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y', strtotime($inscription['date_inscription'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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