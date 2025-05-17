<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $capacite_max = $_POST['capacite_max'];
    $duree_type = $_POST['duree_type'];

    $pdo = connexionBDD();

    $requete = $pdo->prepare("INSERT INTO evenement (titre, description, date_debut, date_fin, capacite_max, duree_type) VALUES (?, ?, ?, ?, ?, ?)"); // les ? sont des places reservées aux valeurs quon va mettre
    $requete->execute([$titre, $description, $date_debut, $date_fin, $capacite_max, $duree_type]);

    echo "L'événement a bien été ajouté.";
}
?>

<h2>Ajouter un événement</h2>
<form method="POST">
    <label>Titre :</label><br>
    <input type="text" name="titre"><br><br>

    <label>Description :</label><br>
    <textarea name="description"></textarea><br><br>

    <label>Date de début :</label><br>
    <input type="date" name="date_debut"><br><br>

    <label>Date de fin :</label><br>
    <input type="date" name="date_fin"><br><br>

    <label>Capacité maximale :</label><br>
    <input type="number" name="capacite_max"><br><br>

    <label>Durée :</label><br>
    <input type="text" name="duree_type"><br><br>

    <input type="submit" value="Ajouter">
</form>
