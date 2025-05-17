<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

//Si user n'est pas connecté, ou alors est connecté mais pas admin, on renvoi un message d'erreur
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}

//Initialisation des variables
$id_jeu = intval($_GET['id']);
$message = '';
$messageType = '';
$erreurs = [];
$jeu = null;
$ressources = [];

//Récupérer les informations du jeu que l'on souhaite
try {
    $conn = connexionBDD();
    $stmt = $conn->prepare('SELECT * FROM jeux WHERE id_jeux = :id');
    $stmt->bindParam(':id', $id_jeu);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        redirigerAvecMessage('liste.php', "Ce jeu n'existe pas...", 'danger');
    }

    $jeu = $stmt->fetch(PDO::FETCH_ASSOC);

    //Recuperer les ressources associés
    $stmt = $conn->prepare("SELECT * FROM ressource WHERE id_jeux = :id_jeux");
    $stmt->bindParam(':id_jeux', $id_jeu);
    $stmt->execute();

    $ressources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des données : " . $e->getMessage();
    $messageType = "danger";
}

//Traitement du formulaire permettant à l'admin d'effectuer la modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //Récupération des données du formulaire
    $nom = $_POST['nom'];
    $description_courte = $_POST['description_courte'];
    $description_longue = $_POST['description_longue'];
    $annee_sortie = $_POST['annee_sortie'];
    $genre = $_POST['genre'];
    $type = $_POST['type'];

    //On vérifie que toutes les données requises soit présentes
    //(NDT: ça fait vraiment spaghetti code mais j'ai rien trouvé d'autres)
    if (estVide($nom)) {
        $erreurs[] = "Le nom du jeu est obligatoire.";
    }

    if (estVide($description_courte)) {
        $erreurs[] = "La description courte est obligatoire.";
    }

    if (estVide($description_longue)) {
        $erreurs[] = "La description longue est obligatoire.";
    }

    if (estVide($annee_sortie)) {
        $erreurs[] = "L'année de sortie est obligatoire.";
    }

    if (!is_numeric($annee_sortie) || $annee_sortie < 1900 || $annee_sortie > date('Y')) {
        $erreurs[] = "L'année de sortie est invalide.";
    }

    if (estVide($genre) || !is_numeric($genre)) {
        $erreurs[] = "Le genre est obligatoire.";
    }

    if (estVide($type) || !is_numeric($type)) {
        $erreurs[] = "Le type est obligatoire.";
    }

    //Check si un jeu avec le même nom existe déjà (pour éviter un doublon)
    if (jeuExiste($nom, $id_jeu)) {
        $erreurs[] = "Un autre jeu avec ce nom existe déjà";
    }

    //Si tout est OK, on modifie le jeu dans la BDD
    if (empty($erreurs)) {
        try {
            $conn = connexionBDD();

            // 1. Mise à jour des données du jeu
            $sql = "UPDATE jeux 
                    SET nom = :nom,
                        description_courte = :description_courte,
                        description_longue = :description_longue,
                        annee_sortie = :annee_sortie,
                        id_genre = :genre,
                        id_type = :type 
                    WHERE id_jeux = :id_jeu";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':nom' => $nom,
                ':description_courte' => $description_courte,
                ':description_longue' => $description_longue,
                ':annee_sortie' => $annee_sortie,
                ':genre' => $genre,
                ':type' => $type,
                ':id_jeu' => $id_jeu
            ]);

            // 2. Traitement des ressources à supprimer
            if (isset($_POST['supprimer_ressource']) && is_array($_POST['supprimer_ressource'])) {
                $stmt_select = $conn->prepare("SELECT url FROM ressource WHERE id_ressource = :id_ressource AND id_jeux = :id_jeux");
                $stmt_delete = $conn->prepare("DELETE FROM ressource WHERE id_ressource = :id_ressource AND id_jeux = :id_jeux");

                foreach ($_POST['supprimer_ressource'] as $id_ressource) {
                    // Récupérer l'URL du fichier avant suppression
                    $stmt_select->execute([
                        ':id_ressource' => $id_ressource,
                        ':id_jeux' => $id_jeu
                    ]);
                    $ressource = $stmt_select->fetch(PDO::FETCH_ASSOC);

                    if ($ressource) {
                        // Supprimer le fichier physique s'il existe
                        $cheminFichier = __DIR__ . '/../../' . $ressource['url'];
                        if (file_exists($cheminFichier)) {
                            unlink($cheminFichier);
                        }

                        // Supprimer l'entrée de la base de données
                        $stmt_delete->execute([
                            ':id_ressource' => $id_ressource,
                            ':id_jeux' => $id_jeu
                        ]);
                    }
                }
            }

            // 3. Traitement de l'image principale
            if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] == 0) {
                $cheminImage = telechargerFichier($_FILES['image_principale'], 'jeux');

                if ($cheminImage) {
                    // Vérifier si une image principale existe déjà
                    $stmt = $conn->prepare("SELECT id_ressource, url FROM ressource WHERE id_jeux = :id_jeux AND titre = 'Image principale'");
                    $stmt->execute([':id_jeux' => $id_jeu]);
                    $imagePrincipale = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($imagePrincipale) {
                        // Supprimer l'ancien fichier
                        $cheminAncienFichier = __DIR__ . '/../../' . $imagePrincipale['url'];
                        if (file_exists($cheminAncienFichier)) {
                            unlink($cheminAncienFichier);
                        }

                        // Mettre à jour l'entrée existante
                        $stmt = $conn->prepare("UPDATE ressource SET url = :url WHERE id_ressource = :id_ressource");
                        $stmt->execute([
                            ':url' => $cheminImage,
                            ':id_ressource' => $imagePrincipale['id_ressource']
                        ]);
                    } else {
                        // Créer une nouvelle entrée
                        $stmt = $conn->prepare("INSERT INTO ressource (id_jeux, type_ressource, url, titre) 
                                           VALUES (:id_jeux, 'image', :url, 'Image principale')");
                        $stmt->execute([
                            ':id_jeux' => $id_jeu,
                            ':url' => $cheminImage
                        ]);
                    }
                }
            }

            // 4. Traitement des nouvelles ressources
            if (isset($_FILES['nouvelles_ressources']) && is_array($_FILES['nouvelles_ressources']['name'])) {
                // Requête préparée pour l'insertion des nouvelles ressources
                $stmt_insert = $conn->prepare("INSERT INTO ressource (id_jeux, type_ressource, url, titre) 
                                         VALUES (:id_jeux, :type_ressource, :url, :titre)");

                // Mapper les types MIME à nos types de ressources
                $typesRessources = [
                    'image' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
                    'video' => ['video/mp4', 'video/avi', 'video/quicktime'],
                    'pdf' => ['application/pdf']
                ];

                for ($i = 0; $i < count($_FILES['nouvelles_ressources']['name']); $i++) {
                    if ($_FILES['nouvelles_ressources']['error'][$i] == 0) {
                        // Créer un tableau pour le fichier courant
                        $fichier = [
                            'name' => $_FILES['nouvelles_ressources']['name'][$i],
                            'type' => $_FILES['nouvelles_ressources']['type'][$i],
                            'tmp_name' => $_FILES['nouvelles_ressources']['tmp_name'][$i],
                            'error' => $_FILES['nouvelles_ressources']['error'][$i],
                            'size' => $_FILES['nouvelles_ressources']['size'][$i]
                        ];

                        $cheminRessource = telechargerFichier($fichier, 'jeux');

                        if ($cheminRessource) {
                            // Déterminer le type de ressource basé sur MIME type
                            $typeRessource = 'autre'; // Par défaut
                            foreach ($typesRessources as $type => $mimeTypes) {
                                if (in_array($fichier['type'], $mimeTypes)) {
                                    $typeRessource = $type;
                                    break;
                                }
                            }

                            // Fallback sur l'extension si le MIME type n'est pas reconnu
                            if ($typeRessource === 'autre') {
                                $extension = strtolower(pathinfo($fichier['name'], PATHINFO_EXTENSION));
                                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    $typeRessource = 'image';
                                } elseif (in_array($extension, ['mp4', 'avi', 'mov'])) {
                                    $typeRessource = 'video';
                                } elseif ($extension === 'pdf') {
                                    $typeRessource = 'pdf';
                                }
                            }

                            // Utiliser le titre fourni ou générer un titre par défaut
                            $titreRessource = !empty($_POST['titre_nouvelles_ressources'][$i])
                                ? $_POST['titre_nouvelles_ressources'][$i]
                                : 'Ressource ' . date('Y-m-d H:i:s');

                            // Enregistrer la ressource
                            $stmt_insert->execute([
                                ':id_jeux' => $id_jeu,
                                ':type_ressource' => $typeRessource,
                                ':url' => $cheminRessource,
                                ':titre' => $titreRessource
                            ]);
                        }
                    }
                }
            }

            // Redirection avec message de succès
            redirigerAvecMessage('liste.php', "Le jeu a été modifié avec succès.", "success");

        } catch (PDOException $e) { //Si jamais il y a une erreur, on renvoi un message d'erreur.
            $message = "Erreur lors de la modification du jeu : " . $e->getMessage();
            $messageType = "danger";
        }
    } else { //Si les infos du jeu rempli sont incorrectes, on renvoi un message d'erreur avec les champs qui sont incorrectes
        $message = "Des erreurs ont été détectées :<br>" . implode("<br>", $erreurs);
        $messageType = "danger";
    }
}

// Récupérer la liste des genres et types pour les menus déroulants
try {
    $conn = connexionBDD();

    // Récupération des genres
    $stmt = $conn->query("SELECT id_genre, nom_genre FROM genre ORDER BY nom_genre");
    $genres = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Récupération des types
    $stmt = $conn->query("SELECT id_type, nom_type FROM type ORDER BY nom_type");
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des données : " . $e->getMessage();
    $messageType = "danger";
}

// Inclure l'en-tête d'administration
include_once '../includes/admin-header.php'; //Le header est à faire, dans includes/admin-header.php
?>

    <div class="container mt-4">
        <h1>Modifier le jeu</h1>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if ($jeu) : ?>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du jeu *</label>
                    <input type="text" class="form-control" id="nom" name="nom" value="<?php echo isset($jeu['nom']) ? $jeu['nom'] : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="description_courte" class="form-label">Description courte *</label>
                    <textarea class="form-control" id="description_courte" name="description_courte" rows="3" required><?php echo isset($jeu['description_courte']) ? $jeu['description_courte'] : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="description_longue" class="form-label">Description longue *</label>
                    <textarea class="form-control" id="description_longue" name="description_longue" rows="6" required><?php echo isset($jeu['description_longue']) ? $jeu['description_longue'] : ''; ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="annee_sortie" class="form-label">Année de sortie *</label>
                    <input type="number" class="form-control" id="annee_sortie" name="annee_sortie" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo isset($jeu['annee_sortie']) ? $jeu['annee_sortie'] : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="genre" class="form-label">Genre *</label>
                    <select class="form-select" id="genre" name="genre" required>
                        <option value="">Sélectionner un genre</option>
                        <?php foreach ($genres as $g) : ?>
                            <option value="<?php echo $g['id_genre']; ?>" <?php if ($g['id_genre'] == $jeu['id_genre']) echo 'selected'; ?>>
                                <?php echo $g['nom_genre']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="type" class="form-label">Type *</label>
                    <select class="form-select" id="type" name="type" required>
                        <option value="">Sélectionner un type</option>
                        <?php foreach ($types as $t) : ?>
                            <option value="<?php echo $t['id_type']; ?>" <?php if ($t['id_type'] == $jeu['id_type']) echo 'selected'; ?>>
                                <?php echo $t['nom_type']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Ressources existantes -->
                <?php if (!empty($ressources)) : ?>
                    <div class="mb-3">
                        <label class="form-label">Ressources existantes</label>
                        <div class="row">
                            <?php foreach ($ressources as $ressource) : ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <?php if ($ressource['type_ressource'] == 'image') : ?>
                                            <img src="/<?php echo $ressource['url']; ?>" class="card-img-top" alt="<?php echo $ressource['titre']; ?>">
                                        <?php elseif ($ressource['type_ressource'] == 'video') : ?>
                                            <div class="ratio ratio-16x9">
                                                <video controls>
                                                    <source src="/<?php echo $ressource['url']; ?>" type="video/mp4">
                                                    Votre navigateur ne prend pas en charge la lecture vidéo.
                                                </video>
                                            </div>
                                        <?php elseif ($ressource['type_ressource'] == 'pdf') : ?>
                                            <div class="text-center p-3">
                                                <i class="bi bi-file-pdf fs-1"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $ressource['titre']; ?></h5>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" value="<?php echo $ressource['id_ressource']; ?>" id="supprimer_<?php echo $ressource['id_ressource']; ?>" name="supprimer_ressource[]">
                                                <label class="form-check-label" for="supprimer_<?php echo $ressource['id_ressource']; ?>">
                                                    Supprimer cette ressource
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Modification de l'image principale -->
                <div class="mb-3">
                    <label for="image_principale" class="form-label">Modifier l'image principale (optionnel)</label>
                    <input type="file" class="form-control" id="image_principale" name="image_principale" accept="image/*">
                    <small class="text-muted">Laissez vide pour conserver l'image actuelle</small>
                </div>

                <!-- Ajout de nouvelles ressources -->
                <div class="mb-3">
                    <label class="form-label">Ajouter de nouvelles ressources</label>
                    <div id="ressources-container">
                        <div class="ressource-item mb-2 d-flex gap-2">
                            <input type="file" class="form-control" name="nouvelles_ressources[]" accept="image/*,.pdf,video/*">
                            <input type="text" class="form-control" name="titre_nouvelles_ressources[]" placeholder="Titre de la ressource">
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="ajouter-ressource">Ajouter une ressource</button>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="liste.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        <?php else : ?>
            <div class="alert alert-danger">
                Impossible de charger les données du jeu.
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('ajouter-ressource').addEventListener('click', function() {
            const container = document.getElementById('ressources-container');
            const nouvelElement = document.createElement('div');
            nouvelElement.className = 'ressource-item mb-2 d-flex gap-2';
            nouvelElement.innerHTML = `
            <input type="file" class="form-control" name="nouvelles_ressources[]" accept="image/*,.pdf,video/*">
            <input type="text" class="form-control" name="titre_nouvelles_ressources[]" placeholder="Titre de la ressource">
            <button type="button" class="btn btn-danger btn-sm" onclick="this.parentElement.remove()">Supprimer</button>
        `;
            container.appendChild(nouvelElement);
        });
    </script>
<?php include_once '../../includes/footer.php'; ?>