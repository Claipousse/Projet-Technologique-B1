<?php
require_once(__DIR__ . '/../config/config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bdd = connexionBDD();

    $email = $_POST["email"];
    $mdp = $_POST["mot_de_passe"];

    $sql = "SELECT * FROM utilisateur WHERE email = ?";
    $stmt = $bdd->prepare($sql);
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur && password_verify($mdp, $utilisateur["mot_de_passe"])) {
        $_SESSION["id_utilisateur"] = $utilisateur["id_utilisateur"];
        $_SESSION["role"] = $utilisateur["role"];
        $_SESSION["nom"] = $utilisateur["nom"];
        $_SESSION["prenom"] = $utilisateur["prenom"];

        if ($utilisateur["role"] == "admin") {
            header("Location: ../admin/index.php"); // tableau de bord admin
        } else {
            header("Location: ../index.php"); //  page d'accueil
        }

        exit();
    } else {
        $erreur = "Mot de passe incorrect ou compte inexistant.";
    }
}
?>

<?php include("../includes/header.php"); ?>

    <div class="page-content">
        <main>
            <div class="auth-container">
                <h1>Connexion</h1>

                <?php if ($erreur): ?>
                    <div class="alert alert-error">
                        <?php echo $erreur; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="form-group">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="mot_de_passe" class="form-label">Mot de passe</label>
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required>
                    </div>

                    <button type="submit" class="btn">Se connecter</button>

                    <p>
                        Pas encore de compte ? <a href="creation-compte.php">Crée-en un ici</a>
                    </p>
                </form>
            </div>
        </main>
    </div>

<?php include("../includes/footer.php"); ?>