<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Traitement de l'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscription') {
    if (!estConnecte()) {
        redirigerAvecMessage('auth/connexion.php', 'Vous devez être connecté pour vous inscrire.', 'error');
    }

    $id_evenement = (int)($_POST['id_evenement'] ?? 0);
    $nb_accompagnants = (int)($_POST['nb_accompagnants'] ?? 0);
    $jeux_preferences = $_POST['jeux_preferences'] ?? [];

    // Validation de la limitation à 3 jeux maximum
    if (!empty($jeux_preferences) && count($jeux_preferences) > 3) {
        redirigerAvecMessage("evenement-detail.php?id=$id_evenement", 'Vous ne pouvez sélectionner que 3 jeux maximum.', 'error');
    }

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
        $stmt = $conn->prepare("SELECT COALESCE(SUM(1 + COALESCE(nb_accompagnant, 0)), 0) FROM inscription WHERE id_evenement = ? AND status = 'validé'");
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
           (SELECT COALESCE(SUM(1 + COALESCE(nb_accompagnant, 0)), 0) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') as nb_inscrits
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

    <main class="evenement-detail">
        <div class="evenement-container">
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
                        <a href="auth/connexion.php" class="btn-inscription">Se connecter</a>
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
                                    Préférences de jeux (optionnel, 3 maximum) :
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

<?php include_once 'includes/footer.php'; ?>