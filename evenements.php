<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Traitement AJAX pour l'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscription') {
    header('Content-Type: application/json');

    if (!estConnecte()) {
        echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour vous inscrire.', 'redirect' => true]);
        exit;
    }

    $id_evenement = (int)($_POST['id_evenement'] ?? 0);
    $nb_accompagnants = (int)($_POST['nb_accompagnants'] ?? 0);

    try {
        $conn = connexionBDD();

        // Vérifier événement existe
        $stmt = $conn->prepare("SELECT * FROM evenement WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);
        $evenement = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$evenement) {
            echo json_encode(['success' => false, 'message' => 'Événement introuvable.']);
            exit;
        }

        // Vérifier si déjà inscrit
        $stmt = $conn->prepare("SELECT COUNT(*) FROM inscription WHERE id_utilisateur = ? AND id_evenement = ? AND status != 'annulé'");
        $stmt->execute([$_SESSION['id_utilisateur'], $id_evenement]);
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Vous êtes déjà inscrit à cet événement.']);
            exit;
        }

        // Vérifier places disponibles
        $stmt = $conn->prepare("SELECT COUNT(*) FROM inscription WHERE id_evenement = ? AND status = 'validé'");
        $stmt->execute([$id_evenement]);
        $nb_inscrits = $stmt->fetchColumn();

        $places_demandees = $nb_accompagnants + 1;
        $places_restantes = $evenement['capacite_max'] - $nb_inscrits;

        if ($places_restantes < $places_demandees) {
            echo json_encode(['success' => false, 'message' => 'Pas assez de places disponibles.']);
            exit;
        }

        // Inscrire
        $stmt = $conn->prepare("INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status) VALUES (?, ?, ?, CURDATE(), 'en attente')");
        $stmt->execute([$_SESSION['id_utilisateur'], $id_evenement, $nb_accompagnants]);

        echo json_encode(['success' => true, 'message' => 'Inscription enregistrée ! Elle sera validée par un administrateur.']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
    }
    exit;
}

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
            $where_conditions[] = "(SELECT COUNT(*) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') >= e.capacite_max";
            break;
        case 'disponible':
            $where_conditions[] = "(SELECT COUNT(*) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') < e.capacite_max";
            break;
        case 'termine':
            $where_conditions[] = "e.date_debut < CURDATE()";
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

    // Requête événements
    $sql = "SELECT e.*, 
                   (SELECT COUNT(*) FROM inscription WHERE id_evenement = e.id_evenement AND status = 'validé') as nb_inscrits,
                   (SELECT GROUP_CONCAT(j.nom SEPARATOR ', ') FROM jeux_evenement je JOIN jeux j ON je.id_jeux = j.id_jeux WHERE je.id_evenement = e.id_evenement) as jeux_liste
            FROM evenement e 
            WHERE $where_clause 
            ORDER BY e.date_debut ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enrichir les données
    foreach ($evenements as &$evenement) {
        $evenement['places_restantes'] = $evenement['capacite_max'] - $evenement['nb_inscrits'];
        $evenement['pourcentage'] = ($evenement['capacite_max'] > 0) ? round(($evenement['nb_inscrits'] / $evenement['capacite_max']) * 100) : 0;
        $evenement['est_complet'] = $evenement['places_restantes'] <= 0;
        $evenement['est_termine'] = strtotime($evenement['date_debut']) < time();

        // Inscription utilisateur
        $evenement['inscription_status'] = null;
        if (estConnecte()) {
            $stmt_user = $conn->prepare("SELECT status FROM inscription WHERE id_utilisateur = ? AND id_evenement = ? AND status != 'annulé'");
            $stmt_user->execute([$_SESSION['id_utilisateur'], $evenement['id_evenement']]);
            $inscription_user = $stmt_user->fetch();
            $evenement['inscription_status'] = $inscription_user ? $inscription_user['status'] : null;
        }
    }

    // Liste des jeux pour le filtre
    $stmt_jeux = $conn->query("SELECT DISTINCT j.nom FROM jeux j JOIN jeux_evenement je ON j.id_jeux = je.id_jeux ORDER BY j.nom");
    $jeux_disponibles = $stmt_jeux->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $evenements = [];
    $jeux_disponibles = [];
}

include_once 'includes/header.php';
?>

    <style>
        /* Page événements */
        .evenements-page { padding-top: 100px; background-color: var(--light-bg); }
        .evenements-container { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        .evenements-title {
            font-family: "Playfair Display", serif;
            color: var(--primary-color);
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .evenements-title::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 2px;
            background-color: var(--primary-color);
        }

        /* Filtres */
        .filtres-evenements {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid #e6ddd0;
        }

        .filtres-ligne { display: flex; align-items: center; gap: 1rem; flex-wrap: nowrap; }
        .filtre-groupe { display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; }
        .filtre-label { font-weight: bold; color: var(--primary-color); font-size: 0.9rem; }

        .filtre-select, .recherche-input {
            padding: 0.5rem;
            border: 1px solid var(--accent-color);
            border-radius: 4px;
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-size: 1rem;
        }

        .filtre-select { min-width: 150px; }

        .recherche-box { flex-grow: 1; position: relative; min-width: 250px; }
        .recherche-input { width: 100%; padding-left: 2rem; }
        .recherche-icon { position: absolute; left: 0.5rem; top: 50%; transform: translateY(-50%); color: var(--primary-color); }

        .btn-filtrer {
            background-color: var(--primary-color);
            color: var(--light-text);
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn-filtrer:hover { background-color: var(--secondary-color); }

        /* Menu jeux */
        .jeux-dropdown { position: relative; min-width: 200px; }

        .jeux-dropdown-btn {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--accent-color);
            border-radius: 4px;
            background-color: var(--light-bg);
            color: var(--dark-text);
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: left;
        }

        .jeux-dropdown-content {
            position: absolute;
            top: calc(100% + 1px);
            left: 0;
            right: 0;
            background-color: white;
            border: 1px solid var(--accent-color);
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 1001;
            max-height: 250px;
            overflow-y: auto;
            display: none;
        }

        .jeux-dropdown-content.show { display: block !important; }

        .jeux-option {
            display: flex;
            align-items: center;
            padding: 0.8rem;
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .jeux-option:hover { background-color: #f8f9fa; }
        .jeux-checkbox { margin-right: 0.8rem; cursor: pointer; }
        .jeux-label { flex-grow: 1; font-size: 0.9rem; cursor: pointer; }

        /* Cartes événements */
        .evenement-carte {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            display: flex;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #e6ddd0;
        }

        .evenement-carte:hover { transform: scale(1.01); box-shadow: 0 6px 16px rgba(0,0,0,0.15); }

        .evenement-date {
            background: linear-gradient(135deg, var(--primary-color), #A0522D);
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95em;
            text-align: center;
            min-width: 130px;
            flex-shrink: 0;
        }

        .evenement-infos { flex-grow: 1; padding: 0.5rem 0; }

        .evenement-titre {
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 0.8rem;
            font-family: "Playfair Display", serif;
            color: var(--primary-color);
            line-height: 1.3;
        }

        .evenement-description { color: #666; margin-bottom: 1rem; line-height: 1.5; }
        .evenement-jeux { margin-bottom: 0.8rem; }
        .evenement-jeux-label { color: var(--primary-color); font-weight: 600; font-size: 0.9rem; }

        .evenement-capacite { margin-top: 0.5rem; }
        .barre-progression { height: 8px; background-color: #e9ecef; border-radius: 4px; overflow: hidden; margin: 5px 0; }
        .barre-remplissage { height: 100%; background: linear-gradient(90deg, #28a745 0%, #20c997 50%, #ffc107 80%, #dc3545 100%); border-radius: 4px; transition: width 0.3s ease; }
        .capacite-texte { color: #495057; font-weight: 500; font-size: 0.9rem; }
        .places-restantes { color: #28a745; }

        /* Actions */
        .evenement-actions { display: flex; flex-direction: column; gap: 0.8rem; min-width: 120px; flex-shrink: 0; }

        .btn-details, .btn-inscrire, .btn-complet, .btn-inscrit, .btn-attente {
            padding: 0.7rem 1rem;
            border-radius: 4px;
            font-weight: 600;
            text-align: center;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            min-height: 40px;
            transition: all 0.3s ease;
        }

        .btn-details { background-color: var(--secondary-color); color: white; text-decoration: none; }
        .btn-details:hover { background-color: var(--primary-color); color: white; text-decoration: none; }

        .btn-inscrire { background-color: var(--primary-color); color: white; border: none; cursor: pointer; }
        .btn-inscrire:hover { background-color: var(--secondary-color); }

        .btn-complet { background-color: #dc3545; color: white; }
        .btn-inscrit { background-color: #28a745; color: white; }
        .btn-attente { background-color: #ffc107; color: #212529; }

        /* Modal */
        .modal-inscription {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            display: none;
            align-items: center;
            justify-content: center;
        }

        .modal-contenu {
            background-color: white;
            padding: 2rem;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .modal-fermer {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: #999;
            cursor: pointer;
        }

        .modal-titre { font-family: "Playfair Display", serif; color: var(--primary-color); margin-bottom: 1.5rem; text-align: center; }
        .modal-evenement { text-align: center; margin-bottom: 1.5rem; color: var(--secondary-color); font-weight: 600; }

        .form-groupe { margin-bottom: 1.5rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: bold; color: var(--dark-text); }
        .form-control { width: 100%; padding: 0.8rem; border: 1px solid var(--accent-color); border-radius: 4px; background-color: var(--light-bg); }

        .modal-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1.5rem; }
        .btn-secondaire, .btn-primaire { padding: 0.8rem 1.5rem; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        .btn-secondaire { background-color: #6c757d; color: white; }
        .btn-primaire { background-color: var(--primary-color); color: white; }

        /* Responsive */
        @media (max-width: 768px) {
            .filtres-ligne { flex-direction: column; align-items: stretch; gap: 1rem; }
            .filtre-select, .recherche-input, .btn-filtrer, .jeux-dropdown { width: 100%; min-width: auto; }
            .evenement-carte { flex-direction: column; text-align: center; gap: 1rem; }
            .evenement-date { min-width: auto; width: 100%; max-width: 200px; margin: 0 auto; }
            .evenement-actions { flex-direction: row; justify-content: center; width: 100%; }
            .btn-details, .btn-inscrire, .btn-complet, .btn-inscrit, .btn-attente { flex: 1; max-width: 120px; }
        }
    </style>

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
                                <option value="termine" <?= $filtre_statut === 'termine' ? 'selected' : '' ?>>Terminés</option>
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
                    <div class="evenement-carte">
                        <div class="evenement-date">
                            <?= formaterDateEvenement($evenement['date_debut'], $evenement['date_fin'], true) ?>
                        </div>

                        <div class="evenement-infos">
                            <div class="evenement-titre"><?= htmlspecialchars($evenement['titre']) ?></div>
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
                                        <div class="barre-remplissage" style="width: <?= $evenement['pourcentage'] ?>%"></div>
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
                                <div class="btn-complet"><i class="fas fa-clock"></i> Terminé</div>
                            <?php elseif ($evenement['est_complet']): ?>
                                <div class="btn-complet"><i class="fas fa-users"></i> Complet</div>
                            <?php elseif ($evenement['inscription_status'] === 'validé'): ?>
                                <div class="btn-inscrit"><i class="fas fa-check-circle"></i> Inscrit</div>
                            <?php elseif ($evenement['inscription_status'] === 'en attente'): ?>
                                <div class="btn-attente"><i class="fas fa-hourglass-half"></i> En attente</div>
                            <?php else: ?>
                                <button class="btn-inscrire" onclick="ouvrirModal(<?= $evenement['id_evenement'] ?>, '<?= htmlspecialchars($evenement['titre']) ?>', <?= $evenement['places_restantes'] ?>)">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </button>
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

    <!-- Modal d'inscription -->
    <div id="modalInscription" class="modal-inscription">
        <div class="modal-contenu">
            <span class="modal-fermer" onclick="fermerModal()">&times;</span>
            <h3 class="modal-titre">Inscription à l'événement</h3>
            <div id="modalEvenementNom" class="modal-evenement"></div>

            <form id="formInscription">
                <input type="hidden" id="modalEventId" name="id_evenement">
                <div class="form-groupe">
                    <label for="nbAccompagnants" class="form-label">Nombre d'accompagnants :</label>
                    <select id="nbAccompagnants" name="nb_accompagnants" class="form-control">
                        <option value="0">Aucun</option>
                        <option value="1">1 accompagnant</option>
                        <option value="2">2 accompagnants</option>
                        <option value="3">3 accompagnants</option>
                        <option value="4">4 accompagnants</option>
                        <option value="5">5 accompagnants</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondaire" onclick="fermerModal()">Annuler</button>
                    <button type="submit" class="btn-primaire">Confirmer l'inscription</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Détecter rafraîchissement et reset filtres
        window.addEventListener('beforeunload', () => sessionStorage.setItem('pageRefreshed', 'true'));
        window.addEventListener('load', function() {
            if (sessionStorage.getItem('pageRefreshed') === 'true') {
                sessionStorage.removeItem('pageRefreshed');
                const urlParams = new URLSearchParams(window.location.search);
                if (urlParams.toString()) window.location.href = window.location.pathname;
            }
        });

        let jeuxDropdownOpen = false;

        function toggleJeuxDropdown(event) {
            event.preventDefault();
            event.stopPropagation();

            const dropdown = document.getElementById('jeuxDropdown');
            const icon = event.target.closest('.jeux-dropdown-btn').querySelector('i');

            jeuxDropdownOpen = !jeuxDropdownOpen;

            if (jeuxDropdownOpen) {
                dropdown.classList.add('show');
                icon.className = 'fas fa-chevron-up';
            } else {
                dropdown.classList.remove('show');
                icon.className = 'fas fa-chevron-down';
            }
        }

        function updateJeuxSelection() {
            const checkboxes = document.querySelectorAll('input[name="jeux[]"]:checked');
            const selectedText = document.getElementById('jeuxSelectedText');

            if (checkboxes.length === 0) {
                selectedText.textContent = 'Tous les jeux';
            } else {
                selectedText.textContent = checkboxes.length + ' jeu' + (checkboxes.length > 1 ? 'x' : '') + ' sélectionné' + (checkboxes.length > 1 ? 's' : '');
            }
        }

        // Fermer dropdown en cliquant à l'extérieur
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.jeux-dropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                document.getElementById('jeuxDropdown').classList.remove('show');
                jeuxDropdownOpen = false;
                const icon = dropdown.querySelector('i');
                if (icon) icon.className = 'fas fa-chevron-down';
            }
        });

        function ouvrirModal(eventId, eventTitle, placesRestantes) {
            <?php if (!estConnecte()): ?>
            if (confirm('Vous devez être connecté pour vous inscrire. Voulez-vous vous connecter maintenant ?')) {
                window.location.href = 'connexion.php';
            }
            return;
            <?php endif; ?>

            document.getElementById('modalEventId').value = eventId;
            document.getElementById('modalEvenementNom').textContent = eventTitle;
            document.getElementById('modalInscription').style.display = 'flex';

            // Limiter accompagnants selon places disponibles
            const select = document.getElementById('nbAccompagnants');
            const maxAccompagnants = Math.min(5, placesRestantes - 1);

            select.innerHTML = '';
            for (let i = 0; i <= maxAccompagnants; i++) {
                const option = document.createElement('option');
                option.value = i;
                option.textContent = i === 0 ? 'Aucun' : i + ' accompagnant' + (i > 1 ? 's' : '');
                select.appendChild(option);
            }
        }

        function fermerModal() {
            document.getElementById('modalInscription').style.display = 'none';
        }

        // Traitement formulaire inscription
        document.getElementById('formInscription').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.append('action', 'inscription');

            fetch('evenements.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        if (data.redirect) {
                            if (confirm(data.message + ' Voulez-vous vous connecter maintenant ?')) {
                                window.location.href = 'connexion.php';
                            }
                        } else {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    alert('Erreur lors de l\'inscription');
                    console.error('Error:', error);
                });
        });

        // Fermer modal en cliquant à l'extérieur
        document.getElementById('modalInscription').addEventListener('click', function(e) {
            if (e.target === this) fermerModal();
        });
    </script>

<?php include_once 'includes/footer.php'; ?>