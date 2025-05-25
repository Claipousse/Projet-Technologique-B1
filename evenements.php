<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Supprimer automatiquement les événements terminés
supprimerEvenementsTermines();

// Récupération des filtres
$filtre_statut = $_GET['statut'] ?? 'tous';
$jeux_selectionnes = $_GET['jeux'] ?? [];
if (!is_array($jeux_selectionnes)) $jeux_selectionnes = [];
$recherche = $_GET['recherche'] ?? '';

try {
    $conn = connexionBDD();

    // Construction des conditions WHERE
    $where_conditions = ["1=1"];
    $params = [];

    // Filtres
    switch ($filtre_statut) {
        case 'complet':
            $where_conditions[] = "(SELECT COALESCE(SUM(1 + COALESCE(nb_accompagnant, 0)), 0) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') >= e.capacite_max";
            break;
        case 'disponible':
            $where_conditions[] = "(SELECT COALESCE(SUM(1 + COALESCE(nb_accompagnant, 0)), 0) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') < e.capacite_max";
            break;
        case 'actif':
            $where_conditions[] = "e.date_debut >= CURDATE()";
            break;
    }

    // Filtre jeux
    if (!empty($jeux_selectionnes)) {
        $jeux_placeholders = [];
        foreach ($jeux_selectionnes as $index => $jeu) {
            $placeholder = ":jeu_$index";
            $jeux_placeholders[] = $placeholder;
            $params[$placeholder] = $jeu;
        }
        $where_conditions[] = "EXISTS (SELECT 1 FROM jeux_evenement je JOIN jeux j ON je.id_jeux = j.id_jeux WHERE je.id_evenement = e.id_evenement AND j.nom IN (" . implode(',', $jeux_placeholders) . "))";
    }

    // Filtre recherche
    if (!empty($recherche)) {
        $where_conditions[] = "(e.titre LIKE :recherche OR e.description LIKE :recherche)";
        $params[':recherche'] = '%' . $recherche . '%';
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Requête simplifiée : événements avec inscriptions validées en haut
    $sql = "SELECT e.*, 
                   (SELECT COALESCE(SUM(1 + COALESCE(nb_accompagnant, 0)), 0) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') as nb_inscrits,
                   (SELECT GROUP_CONCAT(j.nom SEPARATOR ', ') FROM jeux_evenement je JOIN jeux j ON je.id_jeux = j.id_jeux WHERE je.id_evenement = e.id_evenement) as jeux_liste
            FROM evenement e 
            WHERE $where_clause 
            ORDER BY nb_inscrits DESC, e.date_debut ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enrichir les données
    foreach ($evenements as $key => $evenement) {
        $evenements[$key]['places_restantes'] = $evenement['capacite_max'] - $evenement['nb_inscrits'];
        $evenements[$key]['pourcentage'] = ($evenement['capacite_max'] > 0) ? round(($evenement['nb_inscrits'] / $evenement['capacite_max']) * 100) : 0;
        $evenements[$key]['est_complet'] = $evenements[$key]['places_restantes'] <= 0;
        $evenements[$key]['est_termine'] = strtotime($evenement['date_debut']) < strtotime(date('Y-m-d'));

        // Inscription utilisateur
        $evenements[$key]['inscription_status'] = null;
        if (estConnecte()) {
            $stmt_user = $conn->prepare("SELECT status FROM inscription WHERE id_utilisateur = ? AND id_evenement = ? AND status != 'annulé'");
            $stmt_user->execute([$_SESSION['id_utilisateur'], $evenement['id_evenement']]);
            $inscription_user = $stmt_user->fetch();
            $evenements[$key]['inscription_status'] = $inscription_user ? $inscription_user['status'] : null;
        }
    }
    unset($evenement); // Détruit la référence pour éviter les problèmes

    // Liste des jeux pour le filtre
    $stmt_jeux = $conn->query("SELECT DISTINCT j.nom FROM jeux j JOIN jeux_evenement je ON j.id_jeux = je.id_jeux ORDER BY j.nom");
    $jeux_disponibles = $stmt_jeux->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $evenements = [];
    $jeux_disponibles = [];
}

include_once 'includes/header.php';
?>

    <main class="evenements-page">
        <div class="evenements-container">
            <h1 class="evenements-title">Nos Événements</h1>

            <!-- Filtres -->
            <div class="filtres-evenements">
                <form method="GET" action="evenements.php">
                    <div class="filtres-ligne">
                        <div class="filtre-groupe">
                            <span class="filtre-label">Statut:</span>
                            <select name="statut" class="filtre-select">
                                <option value="tous" <?= $filtre_statut === 'tous' ? 'selected' : '' ?>>Tous les événements</option>
                                <option value="actif" <?= $filtre_statut === 'actif' ? 'selected' : '' ?>>À venir</option>
                                <option value="disponible" <?= $filtre_statut === 'disponible' ? 'selected' : '' ?>>Places disponibles</option>
                                <option value="complet" <?= $filtre_statut === 'complet' ? 'selected' : '' ?>>Complets</option>
                            </select>
                        </div>

                        <div class="filtre-groupe">
                            <span class="filtre-label">Jeux:</span>
                            <div class="jeux-dropdown">
                                <button type="button" class="jeux-dropdown-btn" onclick="toggleJeuxDropdown(event)">
                                <span id="jeuxSelectedText">
                                    <?php if (empty($jeux_selectionnes)): ?>
                                        Tous les jeux
                                    <?php else: ?>
                                        <?= count($jeux_selectionnes) ?> jeu<?= count($jeux_selectionnes) > 1 ? 'x' : '' ?> sélectionné<?= count($jeux_selectionnes) > 1 ? 's' : '' ?>
                                    <?php endif; ?>
                                </span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="jeux-dropdown-content" id="jeuxDropdown">
                                    <?php foreach ($jeux_disponibles as $jeu_nom): ?>
                                        <label class="jeux-option" onclick="event.stopPropagation()">
                                            <input type="checkbox" name="jeux[]" value="<?= htmlspecialchars($jeu_nom) ?>"
                                                   class="jeux-checkbox" onchange="updateJeuxSelection()"
                                                <?= in_array($jeu_nom, $jeux_selectionnes) ? 'checked' : '' ?>>
                                            <span class="jeux-label"><?= htmlspecialchars($jeu_nom) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="recherche-box">
                            <i class="fas fa-search recherche-icon"></i>
                            <input type="text" name="recherche" value="<?= htmlspecialchars($recherche) ?>"
                                   placeholder="Rechercher un événement..." class="recherche-input" />
                        </div>

                        <button type="submit" class="btn-filtrer">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Liste des événements -->
            <?php if (!empty($evenements)): ?>
                <?php foreach ($evenements as $evenement): ?>
                    <div class="evenement-carte <?= $evenement['est_termine'] ? 'termine' : '' ?>">
                        <div class="evenement-date">
                            <?= formaterDateEvenement($evenement['date_debut'], $evenement['date_fin'], true) ?>
                        </div>

                        <div class="evenement-infos">
                            <div class="evenement-titre">
                                <?= htmlspecialchars($evenement['titre']) ?>
                                <?php if ($evenement['est_termine']): ?>
                                    <span style="color: #dc3545; font-size: 0.8em; font-weight: normal;">(Terminé)</span>
                                <?php endif; ?>
                            </div>
                            <div class="evenement-description"><?= htmlspecialchars($evenement['description']) ?></div>

                            <?php if ($evenement['jeux_liste']): ?>
                                <div class="evenement-jeux">
                                    <span class="evenement-jeux-label">Jeux : </span>
                                    <?= htmlspecialchars($evenement['jeux_liste']) ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!$evenement['est_complet'] && !$evenement['est_termine']): ?>
                                <div class="evenement-capacite">
                                    <div class="barre-progression">
                                        <div class="barre-remplissage <?= $evenement['est_complet'] ? 'complet' : 'disponible' ?>" style="width: <?= $evenement['pourcentage'] ?>%"></div>
                                    </div>
                                    <div class="capacite-texte">
                                        <?= $evenement['nb_inscrits'] ?>/<?= $evenement['capacite_max'] ?> participants
                                        - <span class="places-restantes"><?= $evenement['places_restantes'] ?> places restantes</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="evenement-actions">
                            <a href="evenement-detail.php?id=<?= $evenement['id_evenement'] ?>" class="btn-details">
                                <i class="fas fa-eye"></i> Détails
                            </a>

                            <?php if ($evenement['est_termine']): ?>
                                <div class="btn-termine"><i class="fas fa-clock"></i> Terminé</div>
                            <?php elseif ($evenement['est_complet']): ?>
                                <div class="btn-complet"><i class="fas fa-users"></i> Complet</div>
                            <?php elseif ($evenement['inscription_status'] === 'validé'): ?>
                                <div class="btn-inscrit"><i class="fas fa-check-circle"></i> Inscrit</div>
                            <?php elseif ($evenement['inscription_status'] === 'en attente'): ?>
                                <div class="btn-attente"><i class="fas fa-hourglass-half"></i> En attente</div>
                            <?php else: ?>
                                <a href="evenement-detail.php?id=<?= $evenement['id_evenement'] ?>#inscription" class="btn-inscrire">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 4rem; color: #666; background: white; border-radius: 10px;">
                    <i class="fas fa-calendar-times" style="font-size: 4rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h3>Aucun événement trouvé</h3>
                    <p>Aucun événement ne correspond à vos critères de recherche.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php include_once 'includes/footer.php'; ?>