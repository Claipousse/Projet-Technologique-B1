<?php
require_once __DIR__ . '/../../config/config.php';

// Vérification de la connexion
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

$pdo = connexionBDD();
$message = '';

// Traitement ajout/modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_type = trim($_POST['nom_type']);
    $id_type = isset($_POST['id_type']) ? intval($_POST['id_type']) : 0;

    if (empty($nom_type)) {
        $message = "Le nom du type est obligatoire.";
    } else {
        try {
            if ($id_type > 0) {
                // Modification
                $requete = $pdo->prepare("UPDATE type SET nom_type = ? WHERE id_type = ?");
                $requete->execute([$nom_type, $id_type]);
                $message = "Type modifié avec succès.";
            } else {
                // Ajout
                $requete = $pdo->prepare("INSERT INTO type (nom_type) VALUES (?)");
                $requete->execute([$nom_type]);
                $message = "Type ajouté avec succès.";
            }
        } catch (PDOException $e) {
            $message = "Erreur : " . $e->getMessage();
        }
    }
}

// Suppression
if (isset($_GET['action']) && $_GET['action'] == 'supprimer' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Vérifier si le type est utilisé
    $requete = $pdo->prepare("SELECT COUNT(*) FROM jeux WHERE id_type = ?");
    $requete->execute([$id]);
    $utilise = $requete->fetchColumn() > 0;

    if ($utilise) {
        $message = "Impossible de supprimer ce type car il est utilisé par des jeux.";
    } else {
        $requete = $pdo->prepare("DELETE FROM type WHERE id_type = ?");
        $requete->execute([$id]);
        $message = "Type supprimé avec succès.";
    }
}

// Récupération pour modification
$type_a_modifier = null;
if (isset($_GET['action']) && $_GET['action'] == 'modifier' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $requete = $pdo->prepare("SELECT * FROM type WHERE id_type = ?");
    $requete->execute([$id]);
    $type_a_modifier = $requete->fetch(PDO::FETCH_ASSOC);
}

// Récupération de tous les types
$requete = $pdo->query("SELECT * FROM type ORDER BY nom_type");
$types = $requete->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <h1>Gestion des types de jeux</h1>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-info"><?php echo $message; ?></div>
        <?php endif; ?>

        <!-- Formulaire d'ajout/modification -->
        <div class="card mb-4">
            <div class="card-header">
                <?php echo $type_a_modifier ? "Modifier un type" : "Ajouter un type"; ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <?php if ($type_a_modifier) : ?>
                        <input type="hidden" name="id_type" value="<?php echo $type_a_modifier['id_type']; ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="nom_type">Nom du type:</label>
                        <input type="text" class="form-control" id="nom_type" name="nom_type"
                               value="<?php echo $type_a_modifier ? htmlspecialchars($type_a_modifier['nom_type']) : ''; ?>" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?php echo $type_a_modifier ? "Modifier" : "Ajouter"; ?>
                    </button>

                    <?php if ($type_a_modifier) : ?>
                        <a href="types.php" class="btn btn-secondary">Annuler</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Liste des types -->
        <div class="card">
            <div class="card-header">Liste des types</div>
            <div class="card-body">
                <?php if (empty($types)) : ?>
                    <p>Aucun type défini.</p>
                <?php else : ?>
                    <table class="table">
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($types as $type) : ?>
                            <tr>
                                <td><?php echo $type['id_type']; ?></td>
                                <td><?php echo htmlspecialchars($type['nom_type']); ?></td>
                                <td>
                                    <a href="types.php?action=modifier&id=<?php echo $type['id_type']; ?>"
                                       class="btn btn-sm btn-primary">Modifier</a>
                                    <a href="types.php?action=supprimer&id=<?php echo $type['id_type']; ?>"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Êtes-vous sûr?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php include_once '../includes/admin-footer.php'; ?>