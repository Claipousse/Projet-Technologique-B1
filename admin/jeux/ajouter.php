<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $annee_sortie = $_POST['annee_sortie'];
    $id_genre = $_POST['genre'];
    $type = $_POST['type'];
    $description_courte = $_POST['description_courte'];
    $description_longue = $_POST['description_longue'];
    $image_url = $_POST['image_url'];

    $pdo = connexionBDD();

    $requete = $pdo->prepare("INSERT INTO jeux (nom, annee_sortie, id_genre, type, description_courte, description_longue, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $requete->execute([$nom, $annee_sortie, $id_genre, $type, $description_courte, $description_longue, $image_url]);

    echo "Le jeu a bien été ajouté.";
}
?>

<h2>Ajouter un jeu</h2>
<form method="POST">
    <label>Nom :</label><br>
    <input type="text" name="nom"><br><br>

    <label>Année de sortie :</label><br>
    <input type="number" name="annee_sortie"><br><br>

    <label>Genre :</label><br>
    <select name="genre">
        <?php include 'get-genres.php'; ?>
    </select><br><br>

    <label>Type :</label><br>
    <input type="text" name="type"><br><br>

    <label>Description courte :</label><br>
    <input type="text" name="description_courte"><br><br>

    <label>Description longue :</label><br>
    <textarea name="description_longue"></textarea><br><br>

    <label>Image (URL) :</label><br>
    <input type="text" name="image_url"><br><br>

    <input type="submit" value="Ajouter">
</form>
