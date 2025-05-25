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

    <style>
        /* Styles spécifiques pour la liste des jeux */
        .jeux-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .jeu-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            border: 1px solid #e6ddd0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .jeu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .jeu-image-container {
            position: relative;
            width: 100%;
            height: 180px;
            overflow: hidden;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        .jeu-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.3s ease;
        }

        .jeu-card:hover .jeu-image {
            transform: scale(1.05);
        }

        .jeu-no-image {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        }

        .jeu-no-image i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            opacity: 0.7;
        }

        .jeu-no-image span {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .jeu-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .jeu-header {
            margin-bottom: 1rem;
        }

        .jeu-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--admin-primary);
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .jeu-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .jeu-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-genre {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .badge-type {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        .badge-year {
            background-color: #e8f5e8;
            color: #388e3c;
        }

        .jeu-descriptions {
            flex: 1;
            margin-bottom: 1rem;
        }

        .description-section {
            margin-bottom: 1rem;
        }

        .description-label {
            font-weight: 600;
            color: var(--admin-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            display: block;
        }

        .description-courte {
            color: #495057;
            font-size: 0.9rem;
            line-height: 1.4;
            margin-bottom: 0.8rem;
        }

        .description-longue {
            color: #495057;
            font-size: 0.9rem;
            line-height: 1.5;
            max-height: 120px;
            overflow-y: auto;
            background-color: #ffffff;
            padding: 1rem;
            border-radius: 6px;
            border: 1px solid #e3e6ea;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
            margin-top: 0.3rem;
        }

        .description-longue::-webkit-scrollbar {
            width: 6px;
        }

        .description-longue::-webkit-scrollbar-track {
            background: #f1f3f4;
            border-radius: 3px;
        }

        .description-longue::-webkit-scrollbar-thumb {
            background: #c1c8cd;
            border-radius: 3px;
        }

        .description-longue::-webkit-scrollbar-thumb:hover {
            background: #a8b2ba;
        }

        .jeu-footer {
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .jeu-date {
            font-size: 0.8rem;
            color: #6c757d;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .jeu-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-modifier {
            background-color: var(--admin-info);
            color: white;
            border: none;
        }

        .btn-modifier:hover {
            background-color: #0056b3;
            color: white;
            text-decoration: none;
        }

        .btn-supprimer {
            background-color: var(--admin-danger);
            color: white;
            border: none;
        }

        .btn-supprimer:hover {
            background-color: #c82333;
            color: white;
            text-decoration: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .jeux-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .jeu-image-container {
                height: 180px;
            }

            .jeu-content {
                padding: 1.2rem;
            }

            .jeu-actions {
                flex-direction: column;
            }
        }

        @media (max-width: 480px) {
            .jeu-image-container {
                height: 160px;
            }

            .jeu-content {
                padding: 1rem;
            }

            .jeu-title {
                font-size: 1.1rem;
            }
        }
    </style>

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