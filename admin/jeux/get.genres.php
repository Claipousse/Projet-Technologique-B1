<?php
require_once __DIR__.'/../../config/config.php';
$pdo = connexionBDD();

$genres = $pdo->query("SELECT id_genre, nom FROM genre")->fetchAll();

foreach ($genres as $genre) {
    echo '<option value="' . $genre['id_genre'] . '">' . htmlspecialchars($genre['nom']) . '</option>';
}
?>