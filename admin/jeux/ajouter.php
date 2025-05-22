<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';

/*
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}
*/

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $annee_sortie = $_POST['annee_sortie'];
    $id_genre = $_POST['genre'];
    $id_type = $_POST['type'];
    $description_courte = trim($_POST['description_courte']);
    $description_longue = trim($_POST['description_longue']);
    $image_path = null;

    // Validation
    if (estVide($nom)) $erreurs[] = "Le nom du jeu est obligatoire.";
    if (estVide($annee_sortie)) $erreurs[] = "L'année de sortie est obligatoire.";
    if (estVide($id_genre)) $erreurs[] = "Le genre est obligatoire.";
    if (estVide($id_type)) $erreurs[] = "Le type est obligatoire.";
    if (estVide($description_courte)) $erreurs[] = "La description courte est obligatoire.";
    if (estVide($description_longue)) $erreurs[] = "La description longue est obligatoire.";
    if (jeuExiste($nom)) $erreurs[] = "Un jeu avec ce nom existe déjà.";

    // Upload image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        if (getimagesize($file['tmp_name']) === false) {
            $erreurs[] = "Le fichier n'est pas une image valide.";
        } else {
            $uploadDir = __DIR__ . '/../../assets/images/jeux/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $file['name'])) {
                $image_path = 'assets/images/jeux/' . $file['name'];
            } else {
                $erreurs[] = "Erreur lors du téléchargement.";
            }
        }
    } else {
        $erreurs[] = "Veuillez sélectionner une image.";
    }

    // Insertion
    if (empty($erreurs)) {
        try {
            $pdo = connexionBDD();
            $pdo->prepare("INSERT INTO jeux (nom, annee_sortie, id_genre, id_type, description_courte, description_longue, image_path, date_ajout) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())")
                ->execute([$nom, $annee_sortie, $id_genre, $id_type, $description_courte, $description_longue, $image_path]);
            redirigerAvecMessage('liste.php', "Le jeu a été ajouté avec succès.", 'success');
        } catch (PDOException $e) {
            $erreurs[] = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupération des données
$pdo = connexionBDD();
$genres = $pdo->query("SELECT id_genre, nom_genre FROM genre ORDER BY nom_genre")->fetchAll();
$types = $pdo->query("SELECT id_type, nom_type FROM type ORDER BY nom_type")->fetchAll();

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
            <h2>Ajouter un jeu</h2>
            <a href="liste.php" class="btn btn-secondary">Retour</a>
        </div>

        <?php if ($erreurs): ?>
            <div class="alert alert-danger">
                <?php foreach ($erreurs as $erreur): ?>
                    <div><?php echo htmlspecialchars($erreur); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Nom *</label>
                <input type="text" class="form-control" name="nom" value="<?php echo isset($_POST['nom']) ? $_POST['nom'] : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Année *</label>
                <input type="number" class="form-control" name="annee_sortie" value="<?php echo isset($_POST['annee_sortie']) ? $_POST['annee_sortie'] : ''; ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Genre *</label>
                <select name="genre" class="form-select" required>
                    <option value="">Sélectionnez</option>
                    <?php foreach ($genres as $g): ?>
                        <option value="<?php echo $g['id_genre']; ?>" <?php echo (isset($_POST['genre']) ? $_POST['genre'] : '') == $g['id_genre'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($g['nom_genre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Type *</label>
                <select name="type" class="form-select" required>
                    <option value="">Sélectionnez</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo $t['id_type']; ?>" <?php echo (isset($_POST['type']) ? $_POST['type'] : '') == $t['id_type'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t['nom_type']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Description courte *</label>
                <textarea class="form-control" name="description_courte" rows="2" required><?php echo isset($_POST['description_courte']) ? $_POST['description_courte'] : ''; ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Description longue *</label>
                <textarea name="description_longue" class="form-control" rows="4" required><?php echo isset($_POST['description_longue']) ? $_POST['description_longue'] : ''; ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Image *</label>
                <input type="file" class="form-control" name="image" id="image" accept="image/*" required>
                <img id="preview" class="mt-2" style="max-width:300px;display:none;">
            </div>

            <button type="submit" class="btn btn-primary">Ajouter</button>
            <a href="liste.php" class="btn btn-secondary">Annuler</a>
        </form>
    </div>

    <script>
        document.getElementById('image').onchange = function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>

<?php include_once '../includes/admin-footer.php'; ?>