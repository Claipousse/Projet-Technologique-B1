<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

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

// Récupérer les 5 prochains événements
try {
    $evenements = $conn->query("SELECT titre, description, date_debut, date_fin, capacite_max
                               FROM evenement
                               WHERE date_debut >= CURDATE()
                               ORDER BY date_debut ASC
                               LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $evenements = [];
}

include_once 'includes/header.php';
?>

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
                    <?php
                    $date = new DateTime($evenement['date_debut']);
                    $mois = ['JAN', 'FÉV', 'MAR', 'AVR', 'MAI', 'JUN', 'JUL', 'AOÛ', 'SEP', 'OCT', 'NOV', 'DÉC'];
                    $mois_nom = $mois[$date->format('n') - 1];
                    $jour = $date->format('d');
                    ?>
                    <div class="event-card">
                        <div class="event-date">
                            <div class="month"><?php echo $mois_nom; ?></div>
                            <div class="day"><?php echo $jour; ?></div>
                        </div>
                        <div class="event-info">
                            <div class="event-title"><?php echo htmlspecialchars($evenement['titre']); ?></div>
                            <div class="event-duration">
                                <?php
                                $date_debut = new DateTime($evenement['date_debut']);
                                $date_fin = new DateTime($evenement['date_fin']);
                                if ($date_debut->format('Y-m-d') === $date_fin->format('Y-m-d')) {
                                    echo "Journée complète";
                                } else {
                                    echo "Du " . $date_debut->format('d/m') . " au " . $date_fin->format('d/m');
                                }
                                ?>
                            </div>
                            <div class="event-meta">
              <span style="color: var(--primary-color); font-weight: bold;">
                Capacité max: <?php echo $evenement['capacite_max']; ?> participants
              </span>
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