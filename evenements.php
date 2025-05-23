<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Récupérer tous les événements avec le calcul des places restantes
try {
    $conn = connexionBDD();
    $stmt = $conn->prepare("
        SELECT 
            e.id_evenement,
            e.titre,
            e.description,
            e.date_debut,
            e.date_fin,
            e.capacite_max,
            e.duree_type,
            COALESCE(SUM(CASE WHEN i.status = 'validé' THEN (1 + COALESCE(i.nb_accompagnant, 0)) ELSE 0 END), 0) as places_prises
        FROM evenement e
        LEFT JOIN inscription i ON e.id_evenement = i.id_evenement
        WHERE e.date_debut >= CURDATE()
        GROUP BY e.id_evenement, e.titre, e.description, e.date_debut, e.date_fin, e.capacite_max, e.duree_type
        ORDER BY e.date_debut ASC
    ");
    $stmt->execute();
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $evenements = [];
    error_log("Erreur lors de la récupération des événements : " . $e->getMessage());
}

// Fonction pour formater la date en français
function formatDateFrancais($date) {
    $mois = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
    ];

    $dateObj = new DateTime($date);
    $jour = $dateObj->format('j');
    $moisNum = (int)$dateObj->format('n');
    $annee = $dateObj->format('Y');

    return $jour . ' ' . $mois[$moisNum] . ' ' . $annee;
}

// Fonction pour obtenir l'emoji selon le titre
function getEmojiParTitre($titre) {
    if (stripos($titre, 'wonders') !== false || stripos($titre, 'catan') !== false) {
        return '🎲';
    } elseif (stripos($titre, 'pandémie') !== false || stripos($titre, 'pandemic') !== false) {
        return '🧬';
    } elseif (stripos($titre, 'dixit') !== false) {
        return '🎨';
    } elseif (stripos($titre, 'uno') !== false || stripos($titre, 'mille bornes') !== false) {
        return '🏁';
    } elseif (stripos($titre, 'mystère') !== false || stripos($titre, 'déduction') !== false) {
        return '🕵️‍♀️';
    } else {
        return '🎮';
    }
}

// Fonction pour obtenir l'icône de durée
function getIconeDuree($durée_type) {
    switch (strtolower($durée_type)) {
        case 'journée':
            return '🕒 Journée';
        case 'demi-journée':
            return '🕒 Demi-journée';
        case 'soirée':
            return '🕒 Soirée';
        case 'week-end':
            return '🕒 Week-end';
        default:
            return '🕒 ' . ucfirst($durée_type);
    }
}

include_once 'includes/header.php';
?>

    <div class="page-content evenements-page">
        <main>
            <h1>Nos Évènements</h1>

            <a href="inscription_formulaire.html" class="inscription-btn">S'inscrire à un événement</a>

            <?php if (!empty($evenements)): ?>
                <?php foreach ($evenements as $evenement): ?>
                    <?php
                    $places_restantes = $evenement['capacite_max'] - $evenement['places_prises'];
                    $emoji = getEmojiParTitre($evenement['titre']);
                    $icone_duree = getIconeDuree($evenement['duree_type']);

                    // Formatage de la date
                    if ($evenement['date_debut'] === $evenement['date_fin']) {
                        $date_affichage = formatDateFrancais($evenement['date_debut']);
                    } else {
                        $date_affichage = formatDateFrancais($evenement['date_debut']) . ' - ' . formatDateFrancais($evenement['date_fin']);
                    }
                    ?>
                    <div class="event">
                        <h2><?php echo $emoji; ?> <?php echo htmlspecialchars($evenement['titre']); ?></h2>
                        <div class="tags">
                            <span class="tag"><?php echo $date_affichage; ?></span>
                            <span class="tag"><?php echo $icone_duree; ?></span>
                            <span class="places"><?php echo $places_restantes; ?> place<?php echo $places_restantes > 1 ? 's' : ''; ?> restante<?php echo $places_restantes > 1 ? 's' : ''; ?></span>
                        </div>
                        <p><?php echo htmlspecialchars($evenement['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="event">
                    <h2>🎮 Aucun événement programmé</h2>
                    <div class="tags">
                        <span class="tag">Bientôt de nouveaux événements</span>
                    </div>
                    <p>Nous préparons de nouveaux événements passionnants ! Revenez bientôt pour découvrir notre programmation.</p>
                </div>
            <?php endif; ?>
        </main>
    </div>

<?php include_once 'includes/footer.php'; ?>