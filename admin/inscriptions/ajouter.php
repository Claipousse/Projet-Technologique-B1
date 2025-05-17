<?php
require_once __DIR__ . '/../../config/config.php';

$pdo = connexionBDD();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $accompagnants = $_POST['accompagnants'];
    $id_evenement = $_POST['id_evenement'];

    // On ajoute le participant
    $stmt1 = $pdo->prepare("INSERT INTO participant (nom, email, accompagnants) VALUES (?, ?, ?)");
    $stmt1->execute([$nom, $email, $accompagnants]);

    $id_participant = $pdo->lastInsertId();

    // On enregistre l’inscription
    $stmt2 = $pdo->prepare("INSERT INTO inscription (id_participant, id_evenement) VALUES (?, ?)");
    $stmt2->execute([$id_participant, $id_evenement]);

    echo "Inscription enregistrée.";
}

// On récupère les événements à afficher
$evenements = $pdo->query("SELECT id_evenement, titre FROM evenement")->fetchAll();
?>

<h2>Inscription à un événement</h2>
<form method="POST">
    <label>Nom :</label><br>
    <input type="text" name="nom"><br><br>

    <label>Email :</label><br>
    <input type="email" name="email"><br><br>

    <label>Accompagnants :</label><br>
    <input type="number" name="accompagnants" min="0"><br><br>

    <label>Choisir un événement :</label><br>
    <select name="id_evenement">
        <?php foreach ($evenements as $event): ?>
            <option value="<?= $event['id_evenement'] ?>">
                <?= htmlspecialchars($event['titre']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <input type="submit" value="S'inscrire">
</form>
