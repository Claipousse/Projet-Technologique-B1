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
    if (estVide($nom)) $erreurs[] = "Le nom du jeu est obligatoire.";
    if (estVide($description_courte)) $erreurs[] = "La description courte est obligatoire.";
    if (estVide($description_longue)) $erreurs[] = "La description longue est obligatoire.";
    if (estVide($annee_sortie)) $erreurs[] = "L'année de sortie est obligatoire.";
    if (!is_numeric($annee_sortie) || $annee_sortie < 1900 || $annee_sortie > date('Y')) $erreurs[] = "L'année de sortie est invalide.";
    if (estVide($genre) || !is_numeric($genre)) $erreurs[] = "Le genre est obligatoire.";
    if (estVide($type) || !is_numeric($type)) $erreurs[] = "Le type est obligatoire.";

    //Check si un jeu avec le même nom existe déjà (pour éviter un doublon)
    if (jeuExiste($nom, $id_jeu)) $erreurs[] = "Un autre jeu avec ce nom existe déjà";

    //Si tout est OK, on modifie le jeu dans la BDD
    if (empty($erreurs)) {
        try {
            $conn = connexionBDD();
            // Mise à jour des données du jeu
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
            <form method="post">
                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du jeu *</label>
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
                    <label for="annee_sortie" class="form-label">Année de sortie *</label>
                    <input type="number" class="form-control" id="annee_sortie" name="annee_sortie" min="1900" max="<?php echo date('Y'); ?>" value="<?php echo htmlspecialchars($jeu['annee_sortie']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="genre" class="form-label">Genre *</label>
                    <select class="form-select" id="genre" name="genre" required>
                        <option value="">Sélectionner un genre</option>
                        <?php foreach ($genres as $g) : ?>
                            <option value="<?php echo $g['id_genre']; ?>" <?php if ($g['id_genre'] == $jeu['id_genre']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($g['nom_genre']); ?>
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
                                <?php echo htmlspecialchars($t['nom_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
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

<?php include_once '../includes/admin-footer.php'; ?>