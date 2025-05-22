<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

/*
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}
*/

// Récupérer les jeux avec toutes les informations
try {
    $conn = connexionBDD();
    $jeux = $conn->query("SELECT j.id_jeux, j.nom, j.annee_sortie, j.image_path, j.description_courte, j.description_longue, j.date_ajout, g.nom_genre, t.nom_type 
                         FROM jeux j
                         JOIN genre g ON j.id_genre = g.id_genre
                         JOIN type t ON j.id_type = t.id_type
                         ORDER BY j.nom")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
}

$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['type']) ? $_GET['type'] : 'info';

include_once '../includes/admin-header.php';
?>

    <div class="container-fluid mt-4">
        <div class="d-flex justify-content-between mb-4">
            <h1>Liste des jeux</h1>
            <a href="ajouter.php" class="btn btn-success">Ajouter un jeu</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (empty($jeux)): ?>
            <div class="alert alert-info">Aucun jeu disponible.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($jeux as $jeu): ?>
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="row g-0 h-100">
                                <div class="col-md-4">
                                    <?php if ($jeu['image_path'] && file_exists('../../' . $jeu['image_path'])): ?>
                                        <img src="../../<?php echo htmlspecialchars($jeu['image_path']); ?>"
                                             alt="<?php echo htmlspecialchars($jeu['nom']); ?>"
                                             class="img-fluid h-100 w-100" style="object-fit:cover;">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center h-100 bg-light text-muted">
                                            <i class="bi bi-image" style="font-size: 3rem;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body d-flex flex-column h-100">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title"><?php echo htmlspecialchars($jeu['nom']); ?></h5>

                                            <div class="mb-2">
                                                <small class="text-muted">
                                                    <strong>Genre:</strong> <?php echo htmlspecialchars($jeu['nom_genre']); ?> |
                                                    <strong>Type:</strong> <?php echo htmlspecialchars($jeu['nom_type']); ?> |
                                                    <strong>Année:</strong> <?php echo htmlspecialchars($jeu['annee_sortie']); ?>
                                                </small>
                                            </div>

                                            <div class="mb-2">
                                                <strong>Description courte:</strong>
                                                <p class="card-text text-muted mb-1"><?php echo htmlspecialchars($jeu['description_courte']); ?></p>
                                            </div>

                                            <div class="mb-2">
                                                <strong>Description longue:</strong>
                                                <p class="card-text text-muted" style="max-height: 100px; overflow-y: auto; font-size: 0.9rem;">
                                                    <?php echo nl2br(htmlspecialchars($jeu['description_longue'])); ?>
                                                </p>
                                            </div>

                                            <?php if ($jeu['date_ajout']): ?>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar"></i> Ajouté le <?php echo date('d/m/Y', strtotime($jeu['date_ajout'])); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>

                                        <div class="mt-3">
                                            <a href="modifier.php?id=<?php echo $jeu['id_jeux']; ?>" class="btn btn-sm btn-primary me-2">
                                                <i class="bi bi-pencil"></i> Modifier
                                            </a>
                                            <a href="suppression.php?id=<?php echo $jeu['id_jeux']; ?>" class="btn btn-sm btn-danger"
                                               onclick="return confirm('Supprimer ce jeu ?');">
                                                <i class="bi bi-trash"></i> Supprimer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>