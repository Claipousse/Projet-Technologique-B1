<?php
require_once(__DIR__ . "/../../config/config.php");
require_once(__DIR__ . "/../admin-header.php");
$pdo = connexionBDD();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = htmlspecialchars($_POST['titre']);
    $description = htmlspecialchars($_POST['description']);
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $capacite = intval($_POST['capacite_max']);
    $duree_type = htmlspecialchars($_POST['duree_type']);

    $sql = "INSERT INTO evenement (titre, description, date_debut, date_fin, capacite_max, duree_type)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$titre, $description, $date_debut, $date_fin, $capacite, $duree_type]);

    echo "<div class='container mt-4'><div class='alert alert-success'>✅ Événement ajouté avec succès !</div></div>";
}
?>

<main class="container py-4">
    <h1 class="text-center mb-4" style="font-family: 'Playfair Display', serif; color: #8B4513;">
        Ajouter un événement
    </h1>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre de l'évènement</label>
                        <input type="text" class="form-control" id="titre" name="titre" required />
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="date_debut" class="form-label">Date de début</label>
                        <input type="date" class="form-control" id="date_debut" name="date_debut" required />
                    </div>

                    <div class="mb-3">
                        <label for="date_fin" class="form-label">Date de fin</label>
                        <input type="date" class="form-control" id="date_fin" name="date_fin" required />
                    </div>

                    <div class="mb-3">
                        <label for="capacite_max" class="form-label">Capacité maximale</label>
                        <input type="number" class="form-control" id="capacite_max" name="capacite_max" min="1" required />
                    </div>

                    <div class="mb-3">
                        <label for="duree_type" class="form-label">Durée</label>
                        <input type="text" class="form-control" id="duree_type" name="duree_type" required />
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Ajouter l'évènement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once(__DIR__ . "/../admin-footer.php"); ?>
