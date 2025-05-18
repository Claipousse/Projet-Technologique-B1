<?php
require_once __DIR__ . '/../../config/config.php';

$pdo = connexionBDD();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $accompagnants = $_POST['accompagnants'];
    $id_evenement = $_POST['id_evenement'];
    $date_inscription = date('Y-m-d');

    // Création de l'utilisateur (avec rôle "participant" par défaut)
    $stmt1 = $pdo->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) VALUES (?, '', ?, '', 'participant')");
    $stmt1->execute([$nom, $email]);
    $id_utilisateur = $pdo->lastInsertId();

    // Insertion de l'inscription (statut = validé)
    $stmt2 = $pdo->prepare("INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status) VALUES (?, ?, ?, ?, 'validé')");
    $stmt2->execute([$id_utilisateur, $id_evenement, $accompagnants, $date_inscription]);

    echo "<p style='color:green;'>Inscription enregistrée avec succès !</p>";
}

// On récupère seulement les événements où il reste de la place
$evenements = $pdo->query("
    SELECT e.id_evenement, e.titre
    FROM evenement e
    WHERE (
        SELECT COUNT(*) + COALESCE(SUM(nb_accompagnant), 0)
        FROM inscription
        WHERE id_evenement = e.id_evenement AND status = 'validé'
    ) < e.capacite_max
")->fetchAll();
?>

<h2>Inscription à un événement</h2>

<?php if (count($evenements) === 0): ?>
    <p style="color:red;">Tous les événements sont complets.</p>
<?php else: ?>
    <form method="POST">
        <label>Nom :</label><br>
        <input type="text" name="nom" required><br><br>

        <label>Email :</label><br>
        <input type="email" name="email" required><br><br>

        <label>Accompagnants :</label><br>
        <input type="number" name="accompagnants" min="0" value="0" required><br><br>

        <label>Choisir un événement :</label><br>
        <select name="id_evenement" required>
            <?php foreach ($evenements as $event): ?>
                <option value="<?= $event['id_evenement'] ?>">
                    <?= htmlspecialchars($event['titre']) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <input type="submit" value="S'inscrire">
    </form>
<?php endif; ?>
