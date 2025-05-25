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

<?php include '../includes/footer.php'; ?>