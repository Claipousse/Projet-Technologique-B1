<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

/*
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}
*/

$id_jeu = intval($_GET['id']);
$erreurs = [];
$jeu = null;

// Récupérer le jeu
try {
    $conn = connexionBDD();
    $stmt = $conn->prepare('SELECT * FROM jeux WHERE id_jeux = :id');
    $stmt->bindParam(':id', $id_jeu);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        redirigerAvecMessage('liste.php', "Ce jeu n'existe pas.", 'danger');
    }
    $jeu = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    redirigerAvecMessage('liste.php', "Erreur : " . $e->getMessage(), 'danger');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $description_courte = trim($_POST['description_courte']);
    $description_longue = trim($_POST['description_longue']);
    $annee_sortie = $_POST['annee_sortie'];
    $genre = $_POST['genre'];
    $type = $_POST['type'];
    $image_path = $jeu['image_path']; // Garder l'ancienne image par défaut

    // Validation
    if (estVide($nom)) $erreurs[] = "Le nom du jeu est obligatoire.";
    if (estVide($description_courte)) $erreurs[] = "La description courte est obligatoire.";
    if (estVide($description_longue)) $erreurs[] = "La description longue est obligatoire.";
    if (estVide($annee_sortie)) $erreurs[] = "L'année de sortie est obligatoire.";
    if (estVide($genre)) $erreurs[] = "Le genre est obligatoire.";
    if (estVide($type)) $erreurs[] = "Le type est obligatoire.";
    if (jeuExiste($nom, $id_jeu)) $erreurs[] = "Un autre jeu avec ce nom existe déjà.";

    // Upload nouvelle image (optionnel)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        if (getimagesize($file['tmp_name']) === false) {
            $erreurs[] = "Le fichier n'est pas une image valide.";
        } else {
            $uploadDir = __DIR__ . '/../../assets/images/jeux/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Supprimer l'ancienne image si elle existe
            if ($jeu['image_path'] && file_exists(__DIR__ . '/../../' . $jeu['image_path'])) {
                unlink(__DIR__ . '/../../' . $jeu['image_path']);
            }

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $file['name'])) {
                $image_path = 'assets/images/jeux/' . $file['name'];
            } else {
                $erreurs[] = "Erreur lors du téléchargement.";
            }
        }
    }

    // Mise à jour
    if (empty($erreurs)) {
        try {
            $conn = connexionBDD();
            $conn->prepare("UPDATE jeux SET nom = ?, description_courte = ?, description_longue = ?, annee_sortie = ?, id_genre = ?, id_type = ?, image_path = ? WHERE id_jeux = ?")
                ->execute([$nom, $description_courte, $description_longue, $annee_sortie, $genre, $type, $image_path, $id_jeu]);
            redirigerAvecMessage('liste.php', "Le jeu a été modifié avec succès.", "success");
        } catch (PDOException $e) {
            $erreurs[] = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupérer genres et types
$conn = connexionBDD();
$genres = $conn->query("SELECT id_genre, nom_genre FROM genre ORDER BY nom_genre")->fetchAll(PDO::FETCH_ASSOC);
$types = $conn->query("SELECT id_type, nom_type FROM type ORDER BY nom_type")->fetchAll(PDO::FETCH_ASSOC);

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
            <h1>Modifier le jeu</h1>
            <a href="liste.php" class="btn btn-secondary">Retour</a>
        </div>

        <?php if ($erreurs): ?>
            <div class="alert alert-danger">
                <?php foreach ($erreurs as $erreur): ?>
                    <div><?php echo htmlspecialchars($erreur); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom *</label>
                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($jeu['nom']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description_courte" class="form-label">Description courte *</label>
                <textarea class="form-control" id="description_courte" name="description_courte" rows="3" required><?php echo htmlspecialchars($jeu['description_courte']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="description_longue" class="form-label">Description longue *</label>
                <textarea class="form-control" id="description_longue" name="description_longue" rows="6" required><?php echo htmlspecialchars($jeu['description_longue']); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="annee_sortie" class="form-label">Année *</label>
                <input type="number" class="form-control" id="annee_sortie" name="annee_sortie" value="<?php echo htmlspecialchars($jeu['annee_sortie']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="genre" class="form-label">Genre *</label>
                <select class="form-select" id="genre" name="genre" required>
                    <option value="">Sélectionner</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo $g['id_genre']; ?>" <?php echo ($g['id_genre'] == $jeu['id_genre']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['nom_genre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="type" class="form-label">Type *</label>
                <select class="form-select" id="type" name="type" required>
                    <option value="">Sélectionner</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo $t['id_type']; ?>" <?php echo ($t['id_type'] == $jeu['id_type']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['nom_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Image actuelle</label>
                <div class="mb-2">
                    <?php if ($jeu['image_path'] && file_exists('../../' . $jeu['image_path'])): ?>
                        <img src="../../<?php echo htmlspecialchars($jeu['image_path']); ?>"
                             alt="Image actuelle" style="max-width:200px;border-radius:4px;">
                    <?php else: ?>
                        <span class="text-muted">Aucune image</span>
                    <?php endif; ?>
                </div>
                <label for="image" class="form-label">Nouvelle image (optionnel)</label>
                <input type="file" class="form-control" name="image" id="image" accept="image/*">
                <div class="form-text">Laissez vide pour conserver l'image actuelle</div>
                <img id="preview" class="mt-2" style="max-width:300px;display:none;">
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="liste.php" class="btn btn-secondary">Annuler</a>
        </form>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>