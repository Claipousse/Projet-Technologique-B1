<?php
require_once __DIR__ . "/../../config.php";
require_once __DIR__ . "/../../admin-header.php";
$pdo = connexionBDD();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $nb_accompagnant = $_POST["nb_accompagnant"];
    $id_evenement = $_POST["id_evenement"];

    $sql = "INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status)
            VALUES ((SELECT id_utilisateur FROM utilisateur WHERE email = ?), ?, ?, CURDATE(), 'validée')";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email, $id_evenement, $nb_accompagnant]);

    header("Location: liste.php");
    exit();
}
?>

<main class="container py-4">
    <h1 class="text-center mb-4" style="font-family: 'Playfair Display', serif; color: #8B4513;">
        ✍️ Ajouter une inscription
    </h1>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm p-4">
                <form method="POST">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" required />
                    </div>

                    <div class="mb-3">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" required />
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required />
                    </div>

                    <div class="mb-3">
                        <label for="nb_accompagnant" class="form-label">Nombre d'accompagnants</label>
                        <input type="number" class="form-control" id="nb_accompagnant" name="nb_accompagnant" min="0" value="0" />
                    </div>

                    <div class="mb-3">
                        <label for="id_evenement" class="form-label">ID de l'événement</label>
                        <input type="number" class="form-control" id="id_evenement" name="id_evenement" required />
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Ajouter l'inscription
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once(__DIR__ . "/../../admin-footer.php"); ?>
