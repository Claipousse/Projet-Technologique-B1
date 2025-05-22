<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Fonction pour formater les dates en français
function formaterDateEvenement($date_debut, $date_fin) {
    $mois = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
    ];

    $debut = new DateTime($date_debut);
    $fin = new DateTime($date_fin);

    if ($debut->format('Y-m-d') === $fin->format('Y-m-d')) {
        // Même jour
        return "Le " . $debut->format('j') . " " . $mois[(int)$debut->format('n')];
    } else {
        // Jours différents
        $jour_debut = $debut->format('j');
        $jour_fin = $fin->format('j');
        $mois_fin = $mois[(int)$fin->format('n')];

        if ($debut->format('n') === $fin->format('n')) {
            // Même mois
            return "Le " . $jour_debut . " & " . $jour_fin . " " . $mois_fin;
        } else {
            // Mois différents - on prend le mois de la fin
            return "Le " . $jour_debut . " & " . $jour_fin . " " . $mois_fin;
        }
    }
}

// Récupérer les 5 derniers jeux ajoutés
try {
    $conn = connexionBDD();
    $jeux = $conn->query("SELECT j.nom, j.description_courte, j.annee_sortie, j.image_path, g.nom_genre, t.nom_type 
                         FROM jeux j
                         JOIN genre g ON j.id_genre = g.id_genre
                         JOIN type t ON j.id_type = t.id_type
                         ORDER BY j.date_ajout DESC
                         LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $jeux = [];
}

// Récupérer les 5 prochains événements avec nombre d'inscrits
try {
    $evenements = $conn->query("SELECT titre, description, date_debut, date_fin, capacite_max, id_evenement
                               FROM evenement
                               WHERE date_debut >= CURDATE()
                               ORDER BY date_debut ASC
                               LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

    // Pour chaque événement, récupérer le nombre d'inscrits
    foreach ($evenements as &$evenement) {
        $sql_count = "SELECT COUNT(*) as nb_inscrits FROM inscription WHERE id_evenement = :id_evenement";
        $stmt_count = $conn->prepare($sql_count);
        $stmt_count->execute([':id_evenement' => $evenement['id_evenement']]);
        $result = $stmt_count->fetch(PDO::FETCH_ASSOC);
        $evenement['nb_inscrits'] = $result['nb_inscrits'];
        $evenement['places_restantes'] = $evenement['capacite_max'] - $evenement['nb_inscrits'];
        $evenement['pourcentage_remplissage'] = ($evenement['capacite_max'] > 0) ?
            round(($evenement['nb_inscrits'] / $evenement['capacite_max']) * 100) : 0;
    }
    unset($evenement);

} catch (PDOException $e) {
    $evenements = [];
}

include_once 'includes/header.php';
?>

    <style>
        .progress-mini {
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 5px 0;
        }

        .progress-bar-mini {
            height: 100%;
            background: linear-gradient(90deg, #28a745 0%, #20c997 50%, #ffc107 80%, #dc3545 100%);
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .event-date-new {
            background: linear-gradient(135deg, #8B4513, #A0522D);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9em;
            text-align: center;
            min-width: 120px;
        }
    </style>

    <section id="home" class="full-section shop-intro">
        <div class="section-content">
            <h1 class="section-heading">Pistache</h1>
            <p class="section-description">
                Bienvenue dans votre boutique spécialisée en jeux de société à
                Bordeaux. Découvrez notre vaste collection de jeux pour tous les
                âges et tous les goûts. De la stratégie aux jeux familiaux, en
                passant par les jeux de rôle et les jeux de cartes, trouvez votre
                prochain coup de cœur ludique chez Pistache.
            </p>
            <a href="#catalogue-events" class="btn">Découvrir nos jeux</a>
        </div>
        <div class="scroll-arrow">
            <a href="#catalogue-events">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
    </section>

    <div id="catalogue-events" class="horizontal-sections">
        <section id="catalogue" class="half-section catalogue-section">
            <div class="section-content">
                <h2 class="section-heading">Notre Catalogue</h2>
                <p class="section-description">
                    Parcourez notre vaste sélection de jeux de société. Des classiques
                    intemporels aux dernières nouveautés, nous avons ce qu'il vous
                    faut pour des heures de divertissement et de plaisir ludique.
                </p>
                <a href="catalogue.php" class="btn">Explorer le catalogue</a>
            </div>
        </section>

        <section id="evenements" class="half-section events-section">
            <div class="section-content">
                <h2 class="section-heading">Nos Événements</h2>
                <p class="section-description">
                    Rejoignez notre communauté de joueurs lors de nos événements
                    réguliers. Tournois, soirées jeux, après-midis famille ou
                    initiations aux jeux de rôle, il y en a pour tous les goûts et
                    tous les niveaux.
                </p>
                <a href="evenements.php" class="btn">Voir le calendrier</a>
            </div>
        </section>
    </div>

    <div class="content-container">
        <section>
            <div class="section-header">
                <h2 class="section-title">Derniers jeux ajoutés</h2>
                <a href="catalogue.php" class="view-all">Voir tout le catalogue</a>
            </div>

            <div class="content-grid">
                <?php if (!empty($jeux)): ?>
                    <?php foreach ($jeux as $jeu): ?>
                        <div class="game-card">
                            <?php if ($jeu['image_path'] && file_exists($jeu['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($jeu['image_path']); ?>"
                                     alt="<?php echo htmlspecialchars($jeu['nom']); ?>"
                                     class="game-image" />
                            <?php else: ?>
                                <div class="game-image" style="background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                    <i class="fas fa-image" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="game-info">
                                <div class="game-title"><?php echo htmlspecialchars($jeu['nom']); ?></div>
                                <p class="game-description">
                                    <?php echo htmlspecialchars($jeu['description_courte']); ?>
                                </p>
                                <div class="game-meta">
                                    <span class="game-year"><?php echo htmlspecialchars($jeu['annee_sortie']); ?></span>
                                    <span class="game-type"><?php echo htmlspecialchars($jeu['nom_type']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1 / -1; text-align: center; color: #666; padding: 2rem;">
                        <i class="fas fa-gamepad" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <p>Aucun jeu disponible pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section>
            <div class="section-header">
                <h2 class="section-title">Prochains événements</h2>
                <a href="evenements.php" class="view-all">Voir tous les événements</a>
            </div>

            <?php if (!empty($evenements)): ?>
                <?php foreach ($evenements as $evenement): ?>
                    <div class="event-card" style="display: flex; align-items: center; gap: 15px; padding: 20px; margin-bottom: 15px; background: #f8f9fa; border-radius: 10px; border-left: 4px solid var(--primary-color);">
                        <div class="event-date-new">
                            <?php echo formaterDateEvenement($evenement['date_debut'], $evenement['date_fin']); ?>
                        </div>
                        <div class="event-info" style="flex: 1;">
                            <div class="event-title" style="font-weight: bold; font-size: 1.1em; margin-bottom: 5px;">
                                <?php echo htmlspecialchars($evenement['titre']); ?>
                            </div>
                            <div class="event-description" style="color: #666; margin-bottom: 10px;">
                                <?php echo htmlspecialchars(substr($evenement['description'], 0, 100)); ?>
                            </div>
                            <div class="event-capacity">
                                <div class="progress-mini">
                                    <div class="progress-bar-mini" style="width: <?php echo $evenement['pourcentage_remplissage']; ?>%"></div>
                                </div>
                                <small style="color: #495057; font-weight: 500;">
                                    <?php echo $evenement['nb_inscrits']; ?>/<?php echo $evenement['capacite_max']; ?> participants
                                    <?php if ($evenement['places_restantes'] > 0): ?>
                                        - <span style="color: #28a745;"><?php echo $evenement['places_restantes']; ?> places restantes</span>
                                    <?php else: ?>
                                        - <span style="color: #dc3545;">Complet</span>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; color: #666; padding: 2rem;">
                    <i class="fas fa-calendar" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <p>Aucun événement programmé pour le moment.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

<?php include_once 'includes/footer.php'; ?>