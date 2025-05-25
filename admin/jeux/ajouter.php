<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

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

    // Upload image principale (obligatoire)
    if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_principale'];
        if (getimagesize($file['tmp_name']) === false) {
            $erreurs[] = "Le fichier image principale n'est pas une image valide.";
        } else {
            $uploadDir = __DIR__ . '/../../assets/images/jeux/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Garder le nom original du fichier
            $fileName = $file['name'];
            $cheminComplet = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $cheminComplet)) {
                $image_path = 'assets/images/jeux/' . $fileName;
            } else {
                $erreurs[] = "Erreur lors du téléchargement de l'image principale.";
            }
        }
    } else {
        $erreurs[] = "L'image principale est obligatoire.";
    }

    // Insertion du jeu
    if (empty($erreurs)) {
        try {
            $pdo = connexionBDD();
            $pdo->beginTransaction();

            // Insérer le jeu
            $stmt = $pdo->prepare("INSERT INTO jeux (nom, annee_sortie, id_genre, id_type, description_courte, description_longue, image_path, date_ajout) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([$nom, $annee_sortie, $id_genre, $id_type, $description_courte, $description_longue, $image_path]);
            $id_jeu = $pdo->lastInsertId();

            // Traitement du PDF (un seul) - stockage dans assets/pdf/
            if (isset($_FILES['pdf']) && $_FILES['pdf']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['pdf'];
                $titre = !empty($_POST['titre_pdf']) ? $_POST['titre_pdf'] : $file['name'];

                // Vérifier l'extension PDF
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if ($file_extension === 'pdf') {
                    $uploadDir = __DIR__ . '/../../assets/pdf/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

                    // Garder le nom original
                    $fileName = $file['name'];
                    $cheminComplet = $uploadDir . $fileName;

                    if (move_uploaded_file($file['tmp_name'], $cheminComplet)) {
                        $url = 'assets/pdf/' . $fileName;
                        $stmt = $pdo->prepare("INSERT INTO ressource (id_jeux, type_ressource, url, titre) VALUES (?, 'pdf', ?, ?)");
                        $stmt->execute([$id_jeu, $url, $titre]);
                    }
                }
            }

            // Traitement de la vidéo (une seule) - Titre maintenant optionnel
            if (!empty($_POST['video_url'])) {
                $url = $_POST['video_url'];
                // Si pas de titre fourni, utiliser un titre par défaut
                $titre = !empty($_POST['titre_video']) ? $_POST['titre_video'] : 'Vidéo de ' . $nom;
                $stmt = $pdo->prepare("INSERT INTO ressource (id_jeux, type_ressource, url, titre) VALUES (?, 'video', ?, ?)");
                $stmt->execute([$id_jeu, $url, $titre]);
            }

            $pdo->commit();
            redirigerAvecMessage('liste.php', "Le jeu a été ajouté avec succès.", 'success');
        } catch (PDOException $e) {
            $pdo->rollback();
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
            <!-- Informations de base -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informations générales</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nom *</label>
                        <input type="text" class="form-control" name="nom" value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Année *</label>
                            <input type="number" class="form-control" name="annee_sortie" value="<?php echo isset($_POST['annee_sortie']) ? $_POST['annee_sortie'] : ''; ?>" required>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description courte *</label>
                        <textarea class="form-control" name="description_courte" rows="2" required><?php echo isset($_POST['description_courte']) ? htmlspecialchars($_POST['description_courte']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description longue *</label>
                        <textarea name="description_longue" class="form-control" rows="4" required><?php echo isset($_POST['description_longue']) ? htmlspecialchars($_POST['description_longue']) : ''; ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Image principale -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Image principale *</h5>
                </div>
                <div class="card-body">
                    <input type="file" class="form-control" name="image_principale" accept="image/*" required>
                    <div class="form-text">Cette image sera utilisée dans le catalogue et comme image principale. Le nom du fichier sera conservé.</div>
                </div>
            </div>

            <!-- Document PDF -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Document PDF (optionnel)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Fichier PDF</label>
                        <input type="file" class="form-control" name="pdf" accept=".pdf">
                        <div class="form-text">Le fichier sera stocké dans /assets/pdf/</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre du document</label>
                        <input type="text" class="form-control" name="titre_pdf" value="<?= isset($_POST['titre_pdf']) ? htmlspecialchars($_POST['titre_pdf']) : '' ?>" placeholder="Ex: Règles du jeu, Guide stratégique...">
                    </div>
                </div>
            </div>

            <!-- Vidéo -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Vidéo YouTube (optionnel)</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Lien YouTube</label>
                        <input type="url" class="form-control" name="video_url" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre de la vidéo (optionnel)</label>
                        <input type="text" class="form-control" name="titre_video" placeholder="Ex: Règles expliquées, Partie commentée...">
                        <div class="form-text">Si aucun titre n'est fourni, un titre par défaut sera généré automatiquement.</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Ajouter le jeu</button>
            <a href="liste.php" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
<?php include_once '../includes/admin-footer.php'; ?>