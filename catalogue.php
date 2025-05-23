<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

// Récupération des paramètres de filtrage
$categorie = $_GET['categorie'] ?? 'all';
$age = $_GET['age'] ?? 'all';
$joueurs = $_GET['joueurs'] ?? 'all';
$tri = $_GET['tri'] ?? 'newest';
$recherche = $_GET['recherche'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$jeux_par_page = 12;
$offset = ($page - 1) * $jeux_par_page;

try {
    $conn = connexionBDD();

    // Construction de la requête de base
    $where_conditions = ["1=1"];
    $params = [];

    // Filtre par genre (catégorie)
    if ($categorie !== 'all') {
        $where_conditions[] = "g.nom_genre = :categorie";
        $params[':categorie'] = $categorie;
    }

    // Filtre par recherche
    if (!empty($recherche)) {
        $where_conditions[] = "(j.nom LIKE :recherche OR j.description_courte LIKE :recherche)";
        $params[':recherche'] = '%' . $recherche . '%';
    }

    // Construction de l'ORDER BY
    $order_by = "j.date_ajout DESC"; // Par défaut : nouveautés
    switch ($tri) {
        case 'popular':
            $order_by = "j.nom ASC"; // En l'absence de système de popularité, tri alphabétique
            break;
        case 'price-low':
        case 'price-high':
            $order_by = "j.nom ASC"; // Pas de prix dans la BD, tri alphabétique
            break;
        case 'newest':
        default:
            $order_by = "j.date_ajout DESC";
            break;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Requête pour compter le total
    $count_query = "SELECT COUNT(*) as total 
                    FROM jeux j 
                    JOIN genre g ON j.id_genre = g.id_genre 
                    JOIN type t ON j.id_type = t.id_type 
                    WHERE $where_clause";

    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_jeux = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_jeux / $jeux_par_page);

    // Requête principale avec pagination
    $query = "SELECT j.*, g.nom_genre, t.nom_type 
              FROM jeux j 
              JOIN genre g ON j.id_genre = g.id_genre 
              JOIN type t ON j.id_type = t.id_type 
              WHERE $where_clause 
              ORDER BY $order_by 
              LIMIT :offset, :limit";

    $stmt = $conn->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $jeux_par_page, PDO::PARAM_INT);
    $stmt->execute();
    $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des genres pour le filtre
    $genres = $conn->query("SELECT DISTINCT nom_genre FROM genre ORDER BY nom_genre")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $jeux = [];
    $genres = [];
    $total_pages = 1;
}

include_once 'includes/header.php';
?>

    <main>
        <section class="catalogue-hero">
            <h1>Notre Catalogue de Jeux</h1>
            <p>
                Découvrez notre sélection variée de jeux de société pour tous les âges,
                tous les goûts et toutes les occasions. Des jeux de stratégie aux jeux d'ambiance,
                en passant par les jeux familiaux et les jeux de rôle, trouvez votre bonheur parmi
                notre collection minutieusement choisie.
            </p>
        </section>

        <div class="catalogue-container">
            <div class="filters">
                <form method="GET" action="catalogue.php">
                    <div class="filter-group">
                        <span class="filter-label">Catégorie:</span>
                        <select name="categorie" class="filter-select">
                            <option value="all" <?= $categorie === 'all' ? 'selected' : '' ?>>Toutes les catégories</option>
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?= htmlspecialchars($genre['nom_genre']) ?>"
                                    <?= $categorie === $genre['nom_genre'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($genre['nom_genre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <span class="filter-label">Trier par:</span>
                        <select name="tri" class="filter-select">
                            <option value="newest" <?= $tri === 'newest' ? 'selected' : '' ?>>Nouveautés</option>
                            <option value="popular" <?= $tri === 'popular' ? 'selected' : '' ?>>Alphabétique</option>
                        </select>
                    </div>

                    <div class="search-box">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="recherche" value="<?= htmlspecialchars($recherche) ?>"
                               placeholder="Rechercher un jeu..." class="search-input" />
                    </div>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                </form>
            </div>

            <div class="games-grid">
                <?php if (!empty($jeux)): ?>
                    <?php foreach ($jeux as $jeu): ?>
                        <div class="game-card">
                            <?php if ($jeu['image_path'] && file_exists($jeu['image_path'])): ?>
                                <img src="<?= htmlspecialchars($jeu['image_path']) ?>"
                                     alt="<?= htmlspecialchars($jeu['nom']) ?>"
                                     class="game-image" />
                            <?php else: ?>
                                <div class="game-image" style="background-color: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">
                                    <i class="fas fa-image" style="font-size: 2rem;"></i>
                                </div>
                            <?php endif; ?>

                            <div class="game-info">
                                <div class="game-title"><?= htmlspecialchars($jeu['nom']) ?></div>
                                <p class="game-description">
                                    <?= htmlspecialchars($jeu['description_courte']) ?>
                                </p>
                                <div class="game-meta">
                                    <span class="game-tag"><?= htmlspecialchars($jeu['nom_genre']) ?></span>
                                    <span class="game-tag"><?= htmlspecialchars($jeu['nom_type']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-gamepad"></i>
                        <p>Aucun jeu trouvé avec ces critères de recherche.</p>
                        <a href="catalogue.php" style="color: var(--primary-color); text-decoration: none;">
                            <i class="fas fa-arrow-left"></i> Voir tous les jeux
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                           class="page-link">
                            <i class="fas fa-angle-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                           class="page-link <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                           class="page-link">
                            <i class="fas fa-angle-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

<?php include_once 'includes/footer.php'; ?>