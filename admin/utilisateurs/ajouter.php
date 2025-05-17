<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $accompagnants = $_POST['accompagnants'];

    $pdo = connexionBDD();

    $requete = $pdo->prepare("INSERT INTO participant (nom, email, accompagnants) VALUES (?, ?, ?)");
    $requete->execute([$nom, $email, $accompagnants]);

    echo "Le participant a bien été ajouté.";
}
?>

<h2>Ajouter un participant</h2>
<form method="POST">
    <label>Nom :</label><br>
    <input type="text" name="nom"><br><br>

    <label>Email :</label><br>
    <input type="email" name="email"><br><br>

    <label>Nombre d'accompagnants :</label><br>
    <input type="number" name="accompagnants" min="0"><br><br>

    <input type="submit" value="Ajouter">
</form>
