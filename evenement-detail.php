<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Traitement de l'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscription') {
    if (!estConnecte()) {
        redirigerAvecMessage('connexion.php', 'Vous devez être connecté pour vous inscrire.', 'error');
    }

    $id_evenement = (int)($_POST['id_evenement'] ?? 0);
    $nb_accompagnants = (int)($_POST['nb_accompagnants'] ?? 0);
    $jeux_preferences = $_POST['jeux_preferences'] ?? [];

    try {
        $conn = connexionBDD();

        // Vérifier si l'événement existe
        $stmt = $conn->prepare("SELECT * FROM evenement WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $evenement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evenement) {
            redirigerAvecMessage('evenements.php', 'Événement introuvable.', 'error');
        }

        // Vérifier si déjà inscrit
        $stmt = $conn->prepare("SELECT COUNT(*) FROM inscription WHERE id_utilisateur = ? AND id_evenement = ? AND status != 'annulé'");
        $stmt->execute([$_SESSION['id_utilisateur'], $id_evenement]);
        if ($stmt->fetchColumn() > 0) {
            redirigerAvecMessage("evenement-detail.php?id=$id_evenement", 'Vous êtes déjà inscrit à cet événement.', 'error');
        }

        // Vérifier les places disponibles (seulement les validés)
        $stmt = $conn->prepare("SELECT COUNT(*) FROM inscription WHERE id_evenement = ? AND status = 'validé'");
        $stmt->execute([$id_evenement]);
        $nb_inscrits = $stmt->fetchColumn();

        $places_demandees = $nb_accompagnants + 1;
        $places_restantes = $evenement['capacite_max'] - $nb_inscrits;

        if ($places_restantes < $places_demandees) {
            redirigerAvecMessage("evenement-detail.php?id=$id_evenement", 'Pas assez de places disponibles.', 'error');
        }

        // Créer l'inscription
        $conn->beginTransaction();

        $stmt = $conn->prepare("INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status) VALUES (?, ?, ?, CURDATE(), 'en attente')");
        $stmt->execute([$_SESSION['id_utilisateur'], $id_evenement, $nb_accompagnants]);
        $id_inscription = $conn->lastInsertId();

        // Ajouter les préférences de jeux si sélectionnées
        if (!empty($jeux_preferences)) {
            $stmt_pref = $conn->prepare("INSERT INTO preferences (id_inscription, id_jeux) VALUES (?, ?)");
            foreach ($jeux_preferences as $id_jeu) {
                $stmt_pref->execute([$id_inscription, $id_jeu]);
            }
        }

        $conn->commit();
        redirigerAvecMessage("evenement-detail.php?id=$id_evenement", 'Inscription enregistrée avec succès ! Elle sera validée par un administrateur.', 'success');

    } catch (PDOException $e) {
        $conn->rollBack();
        redirigerAvecMessage("evenement-detail.php?id=$id_evenement", 'Erreur lors de l\'inscription : ' . $e->getMessage(), 'error');
    }
}

// Récupération de l'événement
$id_evenement = (int)($_GET['id'] ?? 0);

if (!$id_evenement) {
    redirigerAvecMessage('evenements.php', 'Événement introuvable.', 'error');
}

try {
    $conn = connexionBDD();

    // Récupérer l'événement avec les informations d'inscription (seulement les validés)
    $stmt = $conn->prepare("
        SELECT e.*,
               (SELECT COUNT(*) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') as nb_inscrits
        FROM evenement e 
        WHERE e.id_evenement = ?
    ");
    $stmt->execute([$id_evenement]);
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$evenement) {
        redirigerAvecMessage('evenements.php', 'Événement introuvable.', 'error');
    }

    // Calculer les informations de capacité
    $evenement['places_restantes'] = $evenement['capacite_max'] - $evenement['nb_inscrits'];
    $evenement['pourcentage_remplissage'] = ($evenement['capacite_max'] > 0) ?
        round(($evenement['nb_inscrits'] / $evenement['capacite_max']) * 100) : 0;
    $evenement['est_complet'] = $evenement['places_restantes'] <= 0;
    $evenement['est_termine'] = strtotime($evenement['date_debut']) <= time() - 86400; // Terminé seulement le jour suivant

    // Vérifier l'inscription de l'utilisateur connecté
    $inscription_utilisateur = null;
    if (estConnecte()) {
        $stmt = $conn->prepare("SELECT * FROM inscription WHERE id_utilisateur = ? AND id_evenement = ? AND status != 'annulé'");
        $stmt->execute([$_SESSION['id_utilisateur'], $id_evenement]);
        $inscription_utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Récupérer les jeux associés à l'événement
    $stmt = $conn->prepare("
        SELECT j.id_jeux, j.nom, j.description_courte, j.image_path
        FROM jeux_evenement je 
        JOIN jeux j ON je.id_jeux = j.id_jeux 
        WHERE je.id_evenement = ?
        ORDER BY j.nom
    ");
    $stmt->execute([$id_evenement]);
    $jeux_evenement = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    redirigerAvecMessage('evenements.php', 'Erreur lors de la récupération des données.', 'error');
}

include_once 'includes/header.php';
?>

    <style>
        /* Styles spécifiques à la page de détail d'événement */
        .evenement-detail {
            padding-top: 100px;
            background-color: var(--light-bg);
            min-height: calc(100vh - 100px);
        }

        .evenement-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .breadcrumb {
            margin-bottom: 2rem;
            padding: 1rem;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            font-size: 0.9rem;
            border: 1px solid #e6ddd0;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }

        .breadcrumb a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        .breadcrumb-separator {
            margin: 0 0.5rem;
            color: #999;
        }

        .breadcrumb-current {
            color: var(--dark-text);
            font-weight: 600;
        }

        .evenement-hero {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
            overflow: hidden;
            border: 1px solid #e6ddd0;
        }

        .evenement-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .evenement-titre {
            font-family: "Playfair Display", serif;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .evenement-date-duree {
            font-size: 1.2rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }

        .evenement-statut {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .statut-disponible { background-color: #28a745; }
        .statut-complet { background-color: #dc3545; }
        .statut-termine { background-color: #6c757d; }

        .evenement-content {
            padding: 3rem 2rem;
        }

        .evenement-info-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
            margin-bottom: 3rem;
        }

        .evenement-description h3 {
            font-family: "Playfair Display", serif;
            color: var(--primary-color);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .evenement-description p {
            line-height: 1.8;
            font-size: 1.1rem;
            color: var(--dark-text);
            margin-bottom: 1.5rem;
        }

        .evenement-meta {
            background-color: var(--light-bg);
            padding: 2rem;
            border-radius: 8px;
            border: 1px solid #e6ddd0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }

        .meta-item i {
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }

        .meta-item:last-child {
            margin-bottom: 0;
        }

        .capacite-section {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid var(--accent-color);
        }

        .capacite-titre {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .progress-bar {
            height: 12px;
            background-color: #e9ecef;
            border-radius: 6px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 0.3s ease;
        }

        .progress-fill.disponible { background-color: #28a745; }
        .progress-fill.complet { background-color: #dc3545; }

        .capacite-details {
            color: #495057;
            font-weight: 500;
        }

        .places-restantes {
            color: #28a745;
            font-weight: 600;
        }

        .section-title {
            font-family: "Playfair Display", serif;
            color: var(--primary-color);
            font-size: 2rem;
            margin-bottom: 2rem;
            border-bottom: 3px solid var(--accent-color);
            padding-bottom: 0.5rem;
        }

        /* STYLES ADAPTATIFS POUR LES JEUX SELON LE NOMBRE */

        /* Un seul jeu - Affichage complet centré */
        .jeux-grid.single-game {
            display: flex;
            justify-content: center;
            margin-bottom: 3rem;
        }

        .jeux-grid.single-game .jeu-card {
            max-width: 600px;
            width: 100%;
        }

        .jeux-grid.single-game .jeu-image,
        .jeux-grid.single-game .jeu-no-image {
            height: 400px;
        }

        /* Deux jeux - Layout adaptatif */
        .jeux-grid.two-games {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        /* Trois jeux ou plus - Grid standard */
        .jeux-grid.multiple-games {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .jeu-card {
            background-color: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e6ddd0;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        .jeu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .jeu-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .jeu-image:hover {
            transform: scale(1.02);
        }

        .jeu-no-image {
            width: 100%;
            height: 240px;
            background: linear-gradient(135deg, #f0f0f0, #e0e0e0);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #999;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 2px dashed #ddd;
        }

        .jeu-no-image i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }

        .jeu-no-image span {
            font-size: 0.9rem;
            text-align: center;
        }

        .jeu-titre {
            font-family: "Playfair Display", serif;
            color: var(--primary-color);
            font-size: 1.3rem;
            margin-bottom: 0.8rem;
            font-weight: 600;
            line-height: 1.3;
        }

        .jeu-description {
            color: #666;
            font-size: 1rem;
            line-height: 1.5;
            flex-grow: 1;
        }

        /* Styles spéciaux pour un seul jeu */
        .single-game .jeu-titre {
            font-size: 1.8rem;
            text-align: center;
        }

        .single-game .jeu-description {
            font-size: 1.1rem;
            text-align: center;
            line-height: 1.6;
        }

        .single-game .jeu-card {
            padding: 2.5rem;
            min-height: auto;
        }

        .inscription-section {
            background-color: white;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 1px solid #e6ddd0;
        }

        .inscription-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: var(--dark-text);
        }

        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--accent-color);
            border-radius: 4px;
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-family: inherit;
            font-size: 1rem;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(139, 69, 19, 0.2);
        }

        .preferences-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .preference-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .preference-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 2px 5px rgba(139, 69, 19, 0.1);
        }

        .preference-item.selected {
            border-color: var(--primary-color);
            background-color: rgba(139, 69, 19, 0.05);
        }

        .preference-checkbox {
            margin-right: 0.5rem;
        }

        .preference-label {
            cursor: pointer;
            display: flex;
            align-items: center;
            margin: 0;
            font-weight: normal;
        }

        .preference-nom {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.3rem;
        }

        .preference-description {
            font-size: 0.85rem;
            color: #666;
            line-height: 1.3;
        }

        .btn-inscription {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-inscription:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-retour {
            display: inline-block;
            background-color: var(--secondary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .btn-retour:hover {
            background-color: var(--primary-color);
            color: white;
            text-decoration: none;
            transform: translateY(-2px);
        }

        .status-inscrit {
            background-color: #28a745;
            color: white;
            padding: 1rem 2rem;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .status-attente {
            background-color: #ffc107;
            color: #212529;
            padding: 1rem 2rem;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .status-non-connecte {
            background-color: #6c757d;
            color: white;
            padding: 1rem 2rem;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .status-complet {
            background-color: #dc3545;
            color: white;
            padding: 1rem 2rem;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .evenement-container {
                padding: 1rem;
            }

            .evenement-titre {
                font-size: 2rem;
            }

            .evenement-header {
                padding: 2rem 1rem;
            }

            .evenement-content {
                padding: 2rem 1rem;
            }

            .evenement-info-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            /* Responsive pour les jeux */
            .jeux-grid.single-game .jeu-image,
            .jeux-grid.single-game .jeu-no-image {
                height: 300px;
            }

            .jeux-grid.two-games {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .jeux-grid.multiple-games {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .jeu-image, .jeu-no-image {
                height: 200px;
            }

            .single-game .jeu-card {
                padding: 2rem 1.5rem;
            }

            .single-game .jeu-titre {
                font-size: 1.5rem;
            }

            .preferences-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .jeu-image, .jeu-no-image {
                height: 180px;
            }

            .jeux-grid.single-game .jeu-image,
            .jeux-grid.single-game .jeu-no-image {
                height: 250px;
            }

            .jeu-card {
                padding: 1rem;
            }

            .single-game .jeu-card {
                padding: 1.5rem;
            }

            .jeu-titre {
                font-size: 1.1rem;
            }

            .single-game .jeu-titre {
                font-size: 1.3rem;
            }

            .jeu-description {
                font-size: 0.9rem;
            }
        }
    </style>

    <main class="evenement-detail">
        <div class="evenement-container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="index.php">Accueil</a>
                <span class="breadcrumb-separator">></span>
                <a href="evenements.php">Événements</a>
                <span class="breadcrumb-separator">></span>
                <span class="breadcrumb-current"><?= htmlspecialchars($evenement['titre']) ?></span>
            </nav>

            <!-- Bouton retour -->
            <a href="evenements.php" class="btn-retour">
                <i class="fas fa-arrow-left"></i> Retour aux événements
            </a>

            <!-- Affichage des messages -->
            <?php if (isset($_GET['message'])): ?>
                <div class="alert alert-<?= htmlspecialchars($_GET['type'] ?? 'info') ?>">
                    <?= htmlspecialchars($_GET['message']) ?>
                </div>
            <?php endif; ?>

            <!-- Hero de l'événement -->
            <div class="evenement-hero">
                <div class="evenement-header">
                    <h1 class="evenement-titre"><?= htmlspecialchars($evenement['titre']) ?></h1>
                    <div class="evenement-date-duree">
                        <?= formaterDateEvenement($evenement['date_debut'], $evenement['date_fin'], true) ?>
                        • <?= htmlspecialchars($evenement['duree_type']) ?>
                    </div>
                    <div class="evenement-statut <?= $evenement['est_termine'] ? 'statut-termine' : ($evenement['est_complet'] ? 'statut-complet' : 'statut-disponible') ?>">
                        <?php if ($evenement['est_termine']): ?>
                            <i class="fas fa-clock"></i> Événement terminé
                        <?php elseif ($evenement['est_complet']): ?>
                            <i class="fas fa-users"></i> Complet
                        <?php else: ?>
                            <i class="fas fa-check-circle"></i> Places disponibles
                        <?php endif; ?>
                    </div>
                </div>

                <div class="evenement-content">
                    <div class="evenement-info-grid">
                        <!-- Description -->
                        <div class="evenement-description">
                            <h3>Description de l'événement</h3>
                            <p><?= nl2br(htmlspecialchars($evenement['description'])) ?></p>
                        </div>

                        <!-- Informations pratiques -->
                        <div class="evenement-meta">
                            <div class="meta-item">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <strong>Date :</strong><br>
                                    <?= formaterDateEvenement($evenement['date_debut'], $evenement['date_fin']) ?>
                                </div>
                            </div>

                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <strong>Durée :</strong><br>
                                    <?= htmlspecialchars($evenement['duree_type']) ?>
                                </div>
                            </div>

                            <div class="meta-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <strong>Capacité :</strong><br>
                                    <?= $evenement['capacite_max'] ?> participants maximum
                                </div>
                            </div>

                            <?php if (!$evenement['est_termine']): ?>
                                <div class="capacite-section">
                                    <div class="capacite-titre">État des inscriptions</div>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?= $evenement['est_complet'] ? 'complet' : 'disponible' ?>" style="width: <?= $evenement['pourcentage_remplissage'] ?>%"></div>
                                    </div>
                                    <div class="capacite-details">
                                        <?= $evenement['nb_inscrits'] ?>/<?= $evenement['capacite_max'] ?> participants
                                        <?php if (!$evenement['est_complet']): ?>
                                            <br><span class="places-restantes"><?= $evenement['places_restantes'] ?> places restantes</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jeux de l'événement -->
            <?php if (!empty($jeux_evenement)): ?>
                <div class="section">
                    <h2 class="section-title">Jeux de l'événement</h2>
                    <?php
                    $nb_jeux = count($jeux_evenement);
                    $grid_class = '';

                    if ($nb_jeux == 1) {
                        $grid_class = 'single-game';
                    } elseif ($nb_jeux == 2) {
                        $grid_class = 'two-games';
                    } else {
                        $grid_class = 'multiple-games';
                    }
                    ?>
                    <div class="jeux-grid <?= $grid_class ?>">
                        <?php foreach ($jeux_evenement as $jeu): ?>
                            <div class="jeu-card">
                                <?php if ($jeu['image_path'] && file_exists($jeu['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($jeu['image_path']) ?>"
                                         alt="<?= htmlspecialchars($jeu['nom']) ?>" class="jeu-image">
                                <?php else: ?>
                                    <div class="jeu-no-image">
                                        <i class="fas fa-gamepad"></i>
                                        <span>Image non disponible</span>
                                    </div>
                                <?php endif; ?>
                                <div class="jeu-titre"><?= htmlspecialchars($jeu['nom']) ?></div>
                                <div class="jeu-description"><?= htmlspecialchars($jeu['description_courte']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Section d'inscription -->
            <div class="inscription-section" id="inscription">
                <h2 class="section-title">Inscription à l'événement</h2>

                <?php if (!estConnecte()): ?>
                    <div class="status-non-connecte">
                        <i class="fas fa-user-lock"></i>
                        Vous devez être connecté pour vous inscrire à cet événement.
                        <br><br>
                        <a href="connexion.php" class="btn-inscription">Se connecter</a>
                    </div>

                <?php elseif ($evenement['est_termine']): ?>
                    <div class="status-complet">
                        <i class="fas fa-clock"></i>
                        Cet événement est terminé, les inscriptions ne sont plus possibles.
                    </div>

                <?php elseif ($inscription_utilisateur): ?>
                    <?php if ($inscription_utilisateur['status'] === 'validé'): ?>
                        <div class="status-inscrit">
                            <i class="fas fa-check-circle"></i>
                            Vous êtes inscrit à cet événement ! Votre inscription a été validée.
                            <?php if ($inscription_utilisateur['nb_accompagnant'] > 0): ?>
                                <br>Avec <?= $inscription_utilisateur['nb_accompagnant'] ?> accompagnant<?= $inscription_utilisateur['nb_accompagnant'] > 1 ? 's' : '' ?>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="status-attente">
                            <i class="fas fa-hourglass-half"></i>
                            Votre inscription est en attente de validation par un administrateur.
                            <?php if ($inscription_utilisateur['nb_accompagnant'] > 0): ?>
                                <br>Avec <?= $inscription_utilisateur['nb_accompagnant'] ?> accompagnant<?= $inscription_utilisateur['nb_accompagnant'] > 1 ? 's' : '' ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ($evenement['est_complet']): ?>
                    <div class="status-complet">
                        <i class="fas fa-users"></i>
                        Cet événement est complet, les inscriptions ne sont plus possibles.
                    </div>

                <?php else: ?>
                    <form method="POST" class="inscription-form">
                        <input type="hidden" name="action" value="inscription">
                        <input type="hidden" name="id_evenement" value="<?= $evenement['id_evenement'] ?>">

                        <div class="form-group">
                            <label for="nb_accompagnants" class="form-label">
                                Nombre d'accompagnants :
                            </label>
                            <select name="nb_accompagnants" id="nb_accompagnants" class="form-control">
                                <?php
                                $max_accompagnants = min(5, $evenement['places_restantes'] - 1);
                                for ($i = 0; $i <= $max_accompagnants; $i++):
                                    ?>
                                    <option value="<?= $i ?>">
                                        <?= $i === 0 ? 'Aucun accompagnant' : $i . ' accompagnant' . ($i > 1 ? 's' : '') ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <?php if (!empty($jeux_evenement)): ?>
                            <div class="form-group">
                                <label class="form-label">
                                    Préférences de jeux (optionnel) :
                                    <small style="color: #666; font-weight: normal;">Sélectionnez les jeux qui vous intéressent le plus</small>
                                </label>
                                <div class="preferences-grid">
                                    <?php foreach ($jeux_evenement as $jeu): ?>
                                        <div class="preference-item" onclick="togglePreference(<?= $jeu['id_jeux'] ?>)">
                                            <label class="preference-label">
                                                <input type="checkbox" name="jeux_preferences[]" value="<?= $jeu['id_jeux'] ?>"
                                                       class="preference-checkbox" id="jeu_<?= $jeu['id_jeux'] ?>">
                                                <div>
                                                    <div class="preference-nom"><?= htmlspecialchars($jeu['nom']) ?></div>
                                                    <div class="preference-description"><?= htmlspecialchars($jeu['description_courte']) ?></div>
                                                </div>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <button type="submit" class="btn-inscription">
                            <i class="fas fa-user-plus"></i>
                            Confirmer mon inscription
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function togglePreference(jeuId) {
            const checkbox = document.getElementById(`jeu_${jeuId}`);
            const item = checkbox.closest('.preference-item');

            checkbox.checked = !checkbox.checked;

            if (checkbox.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }
        }

        // Empêcher la propagation du clic quand on clique directement sur la checkbox
        document.querySelectorAll('.preference-checkbox').forEach(checkbox => {
            checkbox.addEventListener('click', function(e) {
                e.stopPropagation();
                const item = this.closest('.preference-item');

                if (this.checked) {
                    item.classList.add('selected');
                } else {
                    item.classList.remove('selected');
                }
            });
        });

        // Initialiser l'état des préférences au chargement
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.preference-checkbox:checked').forEach(checkbox => {
                checkbox.closest('.preference-item').classList.add('selected');
            });
        });
    </script>

<?php include_once 'includes/footer.php'; ?>