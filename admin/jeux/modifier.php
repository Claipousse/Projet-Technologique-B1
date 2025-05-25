<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

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

    // Récupérer les ressources existantes
    $stmt_ressources = $conn->prepare('SELECT * FROM ressource WHERE id_jeux = :id ORDER BY type_ressource, titre');
    $stmt_ressources->execute([':id' => $id_jeu]);
    $ressources = $stmt_ressources->fetchAll(PDO::FETCH_ASSOC);

    // Organiser par type (PDF et vidéo seulement)
    $pdf_existant = null;
    $video_existante = null;

    foreach ($ressources as $ressource) {
        switch ($ressource['type_ressource']) {
            case 'pdf':
                $pdf_existant = $ressource;
                break;
            case 'video':
                $video_existante = $ressource;
                break;
        }
    }

} catch (PDOException $e) {
    redirigerAvecMessage('liste.php', "Erreur : " . $e->getMessage(), 'danger');
}

// Traitement de la suppression de ressource
if (isset($_GET['supprimer_ressource'])) {
    $id_ressource = (int)$_GET['supprimer_ressource'];
    try {
        $stmt = $conn->prepare('SELECT * FROM ressource WHERE id_ressource = ? AND id_jeux = ?');
        $stmt->execute([$id_ressource, $id_jeu]);
        $ressource = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ressource) {
            // Supprimer le fichier physique si ce n'est pas une vidéo
            if ($ressource['type_ressource'] !== 'video' && file_exists('../../' . $ressource['url'])) {
                unlink('../../' . $ressource['url']);
            }

            // Supprimer de la base
            $stmt = $conn->prepare('DELETE FROM ressource WHERE id_ressource = ?');
            $stmt->execute([$id_ressource]);

            redirigerAvecMessage('modifier.php?id=' . $id_jeu, "Ressource supprimée avec succès.", 'success');
        }
    } catch (PDOException $e) {
        redirigerAvecMessage('modifier.php?id=' . $id_jeu, "Erreur lors de la suppression : " . $e->getMessage(), 'danger');
    }
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

    // Upload nouvelle image principale (optionnel)
    if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image_principale'];
        if (getimagesize($file['tmp_name']) === false) {
            $erreurs[] = "Le fichier n'est pas une image valide.";
        } else {
            $uploadDir = __DIR__ . '/../../assets/images/jeux/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            // Garder le nom original du fichier
            $fileName = $file['name'];
            $cheminComplet = $uploadDir . $fileName;

            // Supprimer l'ancienne image si elle existe et qu'elle est différente
            if ($jeu['image_path'] && file_exists(__DIR__ . '/../../' . $jeu['image_path']) && $jeu['image_path'] !== 'assets/images/jeux/' . $fileName) {
                unlink(__DIR__ . '/../../' . $jeu['image_path']);
            }

            if (move_uploaded_file($file['tmp_name'], $cheminComplet)) {
                $image_path = 'assets/images/jeux/' . $fileName;
            } else {
                $erreurs[] = "Erreur lors du téléchargement.";
            }
        }
    }

    // Mise à jour
    if (empty($erreurs)) {
        try {
            $conn->beginTransaction();

            // Mettre à jour le jeu
            $conn->prepare("UPDATE jeux SET nom = ?, description_courte = ?, description_longue = ?, annee_sortie = ?, id_genre = ?, id_type = ?, image_path = ? WHERE id_jeux = ?")
                ->execute([$nom, $description_courte, $description_longue, $annee_sortie, $genre, $type, $image_path, $id_jeu]);

            // Traitement du nouveau PDF - stockage dans assets/pdf/
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

                    // Supprimer l'ancien PDF s'il existe
                    if ($pdf_existant) {
                        $stmt = $conn->prepare('DELETE FROM ressource WHERE id_ressource = ?');
                        $stmt->execute([$pdf_existant['id_ressource']]);
                    }

                    if (move_uploaded_file($file['tmp_name'], $cheminComplet)) {
                        $url = 'assets/pdf/' . $fileName;
                        $stmt = $conn->prepare("INSERT INTO ressource (id_jeux, type_ressource, url, titre) VALUES (?, 'pdf', ?, ?)");
                        $stmt->execute([$id_jeu, $url, $titre]);
                    }
                }
            }

            // Traitement de la nouvelle vidéo - Titre maintenant optionnel
            if (!empty($_POST['video_url'])) {
                // Supprimer l'ancienne vidéo si elle existe
                if ($video_existante) {
                    $stmt = $conn->prepare('DELETE FROM ressource WHERE id_ressource = ?');
                    $stmt->execute([$video_existante['id_ressource']]);
                }

                $url = $_POST['video_url'];
                // Si pas de titre fourni, utiliser un titre par défaut
                $titre = !empty($_POST['titre_video']) ? $_POST['titre_video'] : 'Vidéo de ' . $nom;
                $stmt = $conn->prepare("INSERT INTO ressource (id_jeux, type_ressource, url, titre) VALUES (?, 'video', ?, ?)");
                $stmt->execute([$id_jeu, $url, $titre]);
            } elseif (empty($_POST['video_url']) && empty($_POST['titre_video']) && $video_existante) {
                // Si les champs vidéo sont vides et qu'il y avait une vidéo, la supprimer
                $stmt = $conn->prepare('DELETE FROM ressource WHERE id_ressource = ?');
                $stmt->execute([$video_existante['id_ressource']]);
            }

            $conn->commit();
            redirigerAvecMessage('liste.php', "Le jeu a été modifié avec succès.", "success");
        } catch (PDOException $e) {
            $conn->rollback();
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
            <!-- Informations de base -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Informations générales</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom *</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($jeu['nom']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <label for="annee_sortie" class="form-label">Année *</label>
                            <input type="number" class="form-control" id="annee_sortie" name="annee_sortie" value="<?php echo htmlspecialchars($jeu['annee_sortie']); ?>" required>
                        </div>
                        <div class="col-md-4">
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
                        <div class="col-md-4">
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
                    </div>

                    <div class="mb-3">
                        <label for="description_courte" class="form-label">Description courte *</label>
                        <textarea class="form-control" id="description_courte" name="description_courte" rows="3" required><?php echo htmlspecialchars($jeu['description_courte']); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="description_longue" class="form-label">Description longue *</label>
                        <textarea class="form-control" id="description_longue" name="description_longue" rows="6" required><?php echo htmlspecialchars($jeu['description_longue']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Image principale -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Image principale</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label">Image actuelle</label>
                        <div class="mb-2">
                            <?php if ($jeu['image_path'] && file_exists('../../' . $jeu['image_path'])): ?>
                                <img src="../../<?php echo htmlspecialchars($jeu['image_path']); ?>"
                                     alt="Image actuelle" style="max-width:200px;border-radius:4px;">
                            <?php else: ?>
                                <span class="text-muted">Aucune image</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <label for="image_principale" class="form-label">Nouvelle image principale (optionnel)</label>
                    <input type="file" class="form-control" name="image_principale" id="image_principale" accept="image/*">
                    <div class="form-text">Laissez vide pour conserver l'image actuelle. Le nom du fichier sera conservé.</div>
                </div>
            </div>

            <!-- Document PDF existant -->
            <?php if ($pdf_existant): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Document PDF actuel</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-file-pdf text-danger me-2 fs-4"></i>
                                <span><?php echo htmlspecialchars($pdf_existant['titre']); ?></span>
                            </div>
                            <div>
                                <a href="../../<?php echo htmlspecialchars($pdf_existant['url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-eye"></i> Voir
                                </a>
                                <a href="modifier.php?id=<?php echo $id_jeu; ?>&supprimer_ressource=<?php echo $pdf_existant['id_ressource']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Supprimer ce document ?')">
                                    <i class="bi bi-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Nouveau PDF -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo $pdf_existant ? 'Remplacer le document PDF' : 'Ajouter un document PDF'; ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Fichier PDF</label>
                        <input type="file" class="form-control" name="pdf" accept=".pdf">
                        <div class="form-text">Le fichier sera stocké dans /assets/pdf/</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre du document</label>
                        <input type="text" class="form-control" name="titre_pdf"
                               value="<?php echo $pdf_existant ? htmlspecialchars($pdf_existant['titre']) : ''; ?>"
                               placeholder="Ex: Règles du jeu, Guide stratégique...">
                    </div>
                </div>
            </div>

            <!-- Vidéo existante -->
            <?php if ($video_existante): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Vidéo actuelle</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between p-2 border rounded">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-play-circle text-primary me-2 fs-4"></i>
                                <span><?php echo htmlspecialchars($video_existante['titre']); ?></span>
                            </div>
                            <div>
                                <a href="<?php echo htmlspecialchars($video_existante['url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="bi bi-eye"></i> Voir
                                </a>
                                <a href="modifier.php?id=<?php echo $id_jeu; ?>&supprimer_ressource=<?php echo $video_existante['id_ressource']; ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Supprimer cette vidéo ?')">
                                    <i class="bi bi-trash"></i> Supprimer
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Nouvelle vidéo -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><?php echo $video_existante ? 'Remplacer la vidéo' : 'Ajouter une vidéo YouTube'; ?></h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Lien YouTube</label>
                        <input type="url" class="form-control" name="video_url"
                               value="<?php echo $video_existante ? htmlspecialchars($video_existante['url']) : ''; ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                        <?php if ($video_existante): ?>
                            <div class="form-text">Saisissez une nouvelle URL pour remplacer l'actuelle, ou laissez vide pour supprimer</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Titre de la vidéo (optionnel)</label>
                        <input type="text" class="form-control" name="titre_video"
                               value="<?php echo $video_existante ? htmlspecialchars($video_existante['titre']) : ''; ?>"
                               placeholder="Ex: Règles expliquées, Partie commentée...">
                        <div class="form-text">Si aucun titre n'est fourni, un titre par défaut sera généré automatiquement.</div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
            <a href="liste.php" class="btn btn-secondary">Annuler</a>
        </form>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>