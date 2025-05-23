<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Vérifier si un ID de jeu est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: catalogue.php");
    exit();
}

$id_jeu = (int)$_GET['id'];

try {
    $conn = connexionBDD();

    // Récupérer les détails du jeu avec genre et type
    $stmt = $conn->prepare("
        SELECT j.*, g.nom_genre, t.nom_type 
        FROM jeux j
        JOIN genre g ON j.id_genre = g.id_genre
        JOIN type t ON j.id_type = t.id_type
        WHERE j.id_jeux = :id_jeu
    ");
    $stmt->execute([':id_jeu' => $id_jeu]);
    $jeu = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si le jeu n'existe pas, rediriger vers le catalogue
    if (!$jeu) {
        header("Location: catalogue.php");
        exit();
    }

    // Récupérer les ressources associées au jeu (images, vidéos, PDFs)
    $stmt_ressources = $conn->prepare("
        SELECT * FROM ressource 
        WHERE id_jeux = :id_jeu 
        ORDER BY type_ressource, titre
    ");
    $stmt_ressources->execute([':id_jeu' => $id_jeu]);
    $ressources = $stmt_ressources->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur lors de la récupération du jeu : " . $e->getMessage());
    header("Location: catalogue.php");
    exit();
}

include_once 'includes/header.php';
?>

    <div class="page-content">
        <main class="jeu-detail-container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="index.php">Accueil</a>
                <span class="breadcrumb-separator">></span>
                <a href="catalogue.php">Catalogue</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-current"><?= htmlspecialchars($jeu['nom']) ?></span>
            </nav>

            <!-- Section principale du jeu -->
            <section class="jeu-hero">
                <div class="jeu-hero-content">
                    <div class="jeu-image-container">
                        <?php if ($jeu['image_path'] && file_exists($jeu['image_path'])): ?>
                            <img src="<?= htmlspecialchars($jeu['image_path']) ?>"
                                 alt="<?= htmlspecialchars($jeu['nom']) ?>"
                                 class="jeu-main-image">
                        <?php else: ?>
                            <div class="jeu-main-image jeu-no-image">
                                <i class="fas fa-image"></i>
                                <span>Image non disponible</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="jeu-info-container">
                        <h1 class="jeu-title"><?= htmlspecialchars($jeu['nom']) ?></h1>

                        <div class="jeu-meta">
                            <div class="jeu-meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Année de sortie: <?= htmlspecialchars($jeu['annee_sortie']) ?></span>
                            </div>
                            <div class="jeu-meta-item">
                                <i class="fas fa-tag"></i>
                                <span>Genre: <?= htmlspecialchars($jeu['nom_genre']) ?></span>
                            </div>
                            <div class="jeu-meta-item">
                                <i class="fas fa-gamepad"></i>
                                <span>Type: <?= htmlspecialchars($jeu['nom_type']) ?></span>
                            </div>
                        </div>

                        <div class="jeu-description-courte">
                            <h3>Description</h3>
                            <p><?= htmlspecialchars($jeu['description_courte']) ?></p>

                            <!-- Description détaillée intégrée -->
                            <?php if (!empty($jeu['description_longue'])): ?>
                                <div class="jeu-description-longue-inline">
                                    <h4>Règles du jeu</h4>
                                    <div class="description-content">
                                        <?= nl2br(htmlspecialchars($jeu['description_longue'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Ressources supplémentaires -->
            <?php if (!empty($ressources)): ?>
                <section class="jeu-ressources">
                    <div class="content-section">
                        <h2>Ressources supplémentaires</h2>
                        <div class="ressources-grid">
                            <?php foreach ($ressources as $ressource): ?>
                                <div class="ressource-item">
                                    <div class="ressource-icon">
                                        <?php if ($ressource['type_ressource'] === 'video'): ?>
                                            <i class="fas fa-play-circle"></i>
                                        <?php elseif ($ressource['type_ressource'] === 'pdf'): ?>
                                            <i class="fas fa-file-pdf"></i>
                                        <?php else: ?>
                                            <i class="fas fa-image"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ressource-info">
                                        <h4><?= htmlspecialchars($ressource['titre']) ?></h4>
                                        <span class="ressource-type"><?= ucfirst(htmlspecialchars($ressource['type_ressource'])) ?></span>
                                    </div>
                                    <a href="<?= htmlspecialchars($ressource['url']) ?>"
                                       target="_blank"
                                       class="ressource-link">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

        </main>
    </div>

<?php include_once 'includes/footer.php'; ?>