<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Liste des jeux</h1>
            <a href="ajouter.php" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Ajouter un jeu
            </a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (empty($jeux)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Aucun jeu disponible.
            </div>
        <?php else: ?>
            <div class="jeux-grid">
                <?php foreach ($jeux as $jeu): ?>
                    <div class="jeu-card">
                        <div class="jeu-image-container">
                            <?php if ($jeu['image_path'] && file_exists('../../' . $jeu['image_path'])): ?>
                                <img src="../../<?php echo htmlspecialchars($jeu['image_path']); ?>"
                                     alt="<?php echo htmlspecialchars($jeu['nom']); ?>"
                                     class="jeu-image" />
                            <?php else: ?>
                                <div class="jeu-no-image">
                                    <i class="bi bi-image"></i>
                                    <span>Aucune image</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="jeu-content">
                            <div class="jeu-header">
                                <h3 class="jeu-title"><?php echo htmlspecialchars($jeu['nom']); ?></h3>

                                <div class="jeu-meta">
                                    <span class="jeu-badge badge-genre"><?php echo htmlspecialchars($jeu['nom_genre']); ?></span>
                                    <span class="jeu-badge badge-type"><?php echo htmlspecialchars($jeu['nom_type']); ?></span>
                                    <span class="jeu-badge badge-year"><?php echo htmlspecialchars($jeu['annee_sortie']); ?></span>
                                </div>
                            </div>

                            <div class="jeu-descriptions">
                                <div class="description-section">
                                    <span class="description-label">Description courte :</span>
                                    <div class="description-courte">
                                        <?php echo htmlspecialchars($jeu['description_courte']); ?>
                                    </div>
                                </div>

                                <div class="description-section">
                                    <span class="description-label">Description longue :</span>
                                    <div class="description-longue">
                                        <?php echo nl2br(htmlspecialchars($jeu['description_longue'])); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="jeu-footer">
                                <?php if ($jeu['date_ajout']): ?>
                                    <div class="jeu-date">
                                        <i class="bi bi-calendar"></i>
                                        Ajouté le <?php echo date('d/m/Y', strtotime($jeu['date_ajout'])); ?>
                                    </div>
                                <?php endif; ?>

                                <div class="jeu-actions">
                                    <a href="modifier.php?id=<?php echo $jeu['id_jeux']; ?>" class="btn-action btn-modifier">
                                        <i class="bi bi-pencil"></i>
                                        Modifier
                                    </a>
                                    <a href="supprimer.php?id=<?php echo $jeu['id_jeux']; ?>"
                                       class="btn-action btn-supprimer"
                                       onclick="return confirm('Supprimer ce jeu ?');">
                                        <i class="bi bi-trash"></i>
                                        Supprimer
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>