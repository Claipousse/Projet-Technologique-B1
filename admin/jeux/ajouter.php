<?php
require_once __DIR__ . '/../../config/config.php';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $annee_sortie = $_POST['annee_sortie'];
    $id_genre = $_POST['genre'];
    $id_type = $_POST['type']; // Changé pour récupérer l'ID du type
    $description_courte = $_POST['description_courte'];
    $description_longue = $_POST['description_longue'];
    $image_url = $_POST['image_url'];

    $pdo = connexionBDD();

    $requete = $pdo->prepare("INSERT INTO jeux (nom, annee_sortie, id_genre, id_type, description_courte, description_longue, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $requete->execute([$nom, $annee_sortie, $id_genre, $id_type, $description_courte, $description_longue, $image_url]);

    echo "Le jeu a bien été ajouté.";
}

// Récupération des genres pour le formulaire
$pdo = connexionBDD();
$genres = $pdo->query("SELECT id_genre, nom_genre FROM genre")->fetchAll();

// Récupération des types pour le formulaire
$types = $pdo->query("SELECT id_type, nom_type FROM type")->fetchAll();
?>

<h2>Ajouter un jeu</h2>
<form method="POST">
    <label>Nom :</label><br>
    <input type="text" name="nom"><br><br>
    <label>Année de sortie :</label><br>
    <input type="number" name="annee_sortie"><br><br>
    <label>Genre :</label><br>
    <select name="genre">
        <option value="" selected>Sélectionnez un genre</option>
        <?php
        foreach ($genres as $genre) {
            echo '<option value="' . $genre['id_genre'] . '">' . htmlspecialchars($genre['nom_genre']) . '</option>';
        }
        ?>
    </select><br><br>
    <label>Type :</label><br>
    <select name="type">
        <option value="" selected>Sélectionnez un type</option>
        <?php
        foreach ($types as $type) {
            echo '<option value="' . $type['id_type'] . '">' . htmlspecialchars($type['nom_type']) . '</option>';
        }
        ?>
    </select><br><br>
    <label>Description courte :</label><br>
    <input type="text" name="description_courte"><br><br>
    <label>Description longue :</label><br>
    <textarea name="description_longue"></textarea><br><br>
    <label>Image (URL) :</label><br>
    <input type="text" name="image_url"><br><br>
    <input type="submit" value="Ajouter">
</form>