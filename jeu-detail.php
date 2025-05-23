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

    // Récupérer les ressources associées au jeu organisées par type
    $stmt_ressources = $conn->prepare("
        SELECT * FROM ressource 
        WHERE id_jeux = :id_jeu 
        ORDER BY type_ressource, titre
    ");
    $stmt_ressources->execute([':id_jeu' => $id_jeu]);
    $ressources = $stmt_ressources->fetchAll(PDO::FETCH_ASSOC);

    // Organiser les ressources par type (PDF et vidéo seulement)
    $document = null;
    $video = null;

    foreach ($ressources as $ressource) {
        switch ($ressource['type_ressource']) {
            case 'pdf':
                $document = $ressource;
                break;
            case 'video':
                $video = $ressource;
                break;
        }
    }

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

            <!-- Section Document -->
            <?php if ($document): ?>
                <section class="jeu-document">
                    <div class="content-section">
                        <h2>Document</h2>
                        <div class="document-item">
                            <div class="document-icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <div class="document-info">
                                <h4><?= htmlspecialchars($document['titre']) ?></h4>
                                <span class="document-type">Document PDF</span>
                            </div>
                            <a href="<?= htmlspecialchars($document['url']) ?>"
                               target="_blank"
                               class="document-link">
                                <i class="fas fa-download"></i>
                                Télécharger
                            </a>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <!-- Section Vidéo -->
            <?php if ($video): ?>
                <section class="jeu-video">
                    <div class="content-section">
                        <h2>Vidéo</h2>
                        <div class="video-container">
                            <?php
                            // Extraire l'ID YouTube de l'URL pour créer un iframe
                            $video_id = '';
                            if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $video['url'], $matches)) {
                                $video_id = $matches[1];
                            }
                            ?>

                            <?php if ($video_id): ?>
                                <div class="video-wrapper">
                                    <iframe
                                            src="https://www.youtube.com/embed/<?= $video_id ?>"
                                            title="<?= htmlspecialchars($video['titre']) ?>"
                                            frameborder="0"
                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                            allowfullscreen>
                                    </iframe>
                                </div>
                                <div class="video-info">
                                    <h4><?= htmlspecialchars($video['titre']) ?></h4>
                                </div>
                            <?php else: ?>
                                <div class="video-error">
                                    <div class="video-no-thumb">
                                        <i class="fas fa-video"></i>
                                    </div>
                                    <div class="video-info">
                                        <h4><?= htmlspecialchars($video['titre']) ?></h4>
                                        <p>Lien YouTube invalide</p>
                                        <a href="<?= htmlspecialchars($video['url']) ?>"
                                           target="_blank"
                                           class="video-external-link">
                                            <i class="fas fa-external-link-alt"></i>
                                            Voir le lien original
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

        </main>
    </div>

    <!-- Modal pour les images (plus nécessaire mais gardé pour compatibilité) -->
    <div id="imageModal" class="modal" onclick="fermerModal()">
        <div class="modal-content">
            <span class="close" onclick="fermerModal()">&times;</span>
            <img id="modalImage" src="" alt="">
            <div id="modalTitle"></div>
        </div>
    </div>

    <script>
        // Modal functions (gardées pour compatibilité mais plus nécessaires)
        function ouvrirModal(src, title) {
            document.getElementById('modalImage').src = src;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('imageModal').style.display = 'block';
        }

        function fermerModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Fermer le modal avec la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                fermerModal();
            }
        });
    </script>

<?php include_once 'includes/footer.php'; ?>