<?php
require_once '../config/config.php';
require_once '../includes/fonctions.php';

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    rediriger('connexion.php');
}

$id_utilisateur = $_SESSION['id_utilisateur'];

// Afficher les messages d'alerte
$message = '';
if (isset($_GET['message'])) {
    $type = isset($_GET['type']) ? $_GET['type'] : 'info';
    $message = alerte(htmlspecialchars($_GET['message']), $type);
}

try {
    $inscriptions = getInscriptionsUtilisateur($id_utilisateur);
} catch (Exception $e) {
    $message = alerte("Erreur lors de la récupération de vos inscriptions : " . $e->getMessage(), 'danger');
    $inscriptions = [];
}

include '../includes/header.php';
?>

    <main class="page-content">
        <section class="mes-inscriptions">
            <h1><i class="fas fa-calendar-check"></i> Mes inscriptions</h1>

            <?php echo $message; ?>

            <?php if (empty($inscriptions)): ?>
                <div class="no-inscriptions">
                    <h3>Aucune inscription trouvée</h3>
                    <p>Vous n'êtes inscrit à aucun événement pour le moment.</p>
                </div>
            <?php else: ?>
                <div class="inscriptions-grid">
                    <?php foreach ($inscriptions as $inscription): ?>
                        <div class="inscription-card">
                            <div class="inscription-header">
                                <h3><?php echo htmlspecialchars($inscription['titre']); ?></h3>
                                <span class="status status-<?php echo $inscription['status']; ?>">
                                <?php
                                switch($inscription['status']) {
                                    case 'en attente':
                                        echo '<i class="fas fa-clock"></i> En attente';
                                        break;
                                    case 'validé':
                                        echo '<i class="fas fa-check-circle"></i> Validé';
                                        break;
                                }
                                ?>
                            </span>
                            </div>

                            <div class="inscription-info">
                                <p class="description">
                                    <?php echo nl2br(htmlspecialchars($inscription['description'])); ?>
                                </p>

                                <div class="date-info">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo formaterDateEvenement($inscription['date_debut'], $inscription['date_fin'], true); ?></span>
                                    <span class="duree">(<?php echo htmlspecialchars($inscription['duree_type']); ?>)</span>
                                </div>

                                <div class="participants-info">
                                    <i class="fas fa-users"></i>
                                    <span>
                                    <?php
                                    $nb_total = 1 + ($inscription['nb_accompagnant'] ?? 0);
                                    echo $nb_total . ' participant' . ($nb_total > 1 ? 's' : '');
                                    if ($inscription['nb_accompagnant'] > 0) {
                                        echo ' (vous + ' . $inscription['nb_accompagnant'] . ' accompagnant' . ($inscription['nb_accompagnant'] > 1 ? 's' : '') . ')';
                                    }
                                    ?>
                                </span>
                                </div>

                                <div class="inscription-date">
                                    <i class="fas fa-clock"></i>
                                    <span>Inscrit le <?php echo date('d/m/Y', strtotime($inscription['date_inscription'])); ?></span>
                                </div>

                                <?php
                                // Récupérer les préférences de jeux pour cette inscription
                                try {
                                    $preferences = getPreferencesInscription($inscription['id_inscription']);
                                    ?>
                                    <div class="preferences-jeux">
                                        <h4><i class="fas fa-gamepad"></i> Jeux préférés :</h4>
                                        <?php if (!empty($preferences)): ?>
                                            <div class="jeux-list">
                                                <?php foreach ($preferences as $jeu): ?>
                                                    <span class="jeu-tag"><?php echo htmlspecialchars($jeu['nom']); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="no-preferences">Pas de préférences</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                } catch (Exception $e) {
                                    // En cas d'erreur, afficher "Pas de préférences"
                                    ?>
                                    <div class="preferences-jeux">
                                        <h4><i class="fas fa-gamepad"></i> Jeux préférés :</h4>
                                        <span class="no-preferences">Pas de préférences</span>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>

                            <div class="inscription-actions">
                                <form method="POST" action="desinscription.php" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir vous désinscrire de cet événement ?')">
                                    <input type="hidden" name="id_inscription" value="<?php echo $inscription['id_inscription']; ?>">
                                    <button type="submit" class="btn-desinscription">
                                        <i class="fas fa-times"></i> Se désinscrire
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <style>
        .mes-inscriptions {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .page-content {
            padding-top: 100px;
            min-height: calc(100vh - 100px);
            margin: 0;
            width: 100vw;
            box-sizing: border-box;
        }

        .mes-inscriptions h1 {
            font-family: "Playfair Display", serif;
            color: var(--primary-color);
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .mes-inscriptions h1::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 2px;
            background-color: var(--primary-color);
        }

        .no-inscriptions {
            text-align: center;
            padding: 2rem;
            margin-top: 2rem;
        }

        .no-inscriptions h3 {
            color: var(--secondary-color);
            margin-bottom: 1rem;
        }

        .no-inscriptions p {
            color: var(--dark-text);
            margin-bottom: 0;
        }

        .inscriptions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .inscription-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid var(--accent-color);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .inscription-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            border-color: var(--primary-color);
        }

        .inscription-header {
            background: var(--secondary-color);
            color: var(--light-text);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .inscription-header h3 {
            margin: 0;
            font-size: 1.2rem;
            font-family: "Playfair Display", serif;
        }

        .status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-en-attente {
            background: #ff8c00;
            color: white;
            border: 1px solid #e67e00;
        }

        .status-validé {
            background: #28a745;
            color: white;
            border: 1px solid #1e7e34;
        }

        .inscription-info {
            padding: 1.5rem;
            background-color: var(--light-bg);
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .inscription-info p.description {
            margin-bottom: 1rem;
            color: var(--dark-text);
            line-height: 1.6;
        }

        .date-info, .participants-info, .inscription-date {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: var(--dark-text);
        }

        .date-info i, .participants-info i, .inscription-date i {
            margin-right: 0.8rem;
            width: 16px;
            color: var(--primary-color);
        }

        .duree {
            font-style: italic;
            margin-left: 0.5rem;
            color: var(--secondary-color);
        }

        .preferences-jeux {
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid var(--accent-color);
        }

        .preferences-jeux h4 {
            color: var(--secondary-color);
            font-size: 1rem;
            margin-bottom: 0.8rem;
            font-family: "Playfair Display", serif;
        }

        .jeux-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .jeu-tag {
            background: var(--accent-color);
            color: var(--dark-text);
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .no-preferences {
            color: #666;
            font-style: italic;
            font-size: 0.9rem;
        }

        .inscription-actions {
            padding: 1rem 1.5rem;
            background: var(--secondary-color);
            border-top: 1px solid var(--accent-color);
        }

        .btn-desinscription {
            background-color: #dc3545;
            color: var(--light-text);
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: bold;
            transition: all 0.3s ease;
            font-family: "Playfair Display", serif;
            letter-spacing: 1px;
        }

        .btn-desinscription:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

    </style>

<?php include '../includes/footer.php'; ?>