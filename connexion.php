<?php
require_once(__DIR__ . '/config/config.php');
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

        if ($utilisateur["role"] == "admin") {
            header("Location: admin/index.php"); // tableau de bord admin
        } else {
            header("Location: index.php"); //  page d’accueil
        }

        exit();
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}
?>

<?php include("header.php"); ?>

<main style="padding-top: 120px; padding-bottom: 80px; background-color: #f5f5dc;">
    <div style="max-width: 500px; margin: 0 auto; background-color: white; padding: 2rem; border-radius: 8px; border: 1px solid #d2b48c; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1 style="text-align: center; font-family: 'Playfair Display', serif; font-size: 2rem; color: #8b4513;">Connexion</h1>

        <?php if ($erreur): ?>
            <p style="color: red; text-align: center;"><?php echo $erreur; ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <label for="email" style="font-weight: bold; display: block; margin-top: 1rem;">Adresse email</label>
            <input type="email" name="email" required style="width: 100%; padding: 0.8rem; margin-top: 0.3rem; border: 1px solid #d2b48c; border-radius: 4px; background-color: #f5f5dc;">

            <label for="mot_de_passe" style="font-weight: bold; display: block; margin-top: 1.2rem;">Mot de passe</label>
            <input type="password" name="mot_de_passe" required style="width: 100%; padding: 0.8rem; margin-top: 0.3rem; border: 1px solid #d2b48c; border-radius: 4px; background-color: #f5f5dc;">

            <button type="submit" style="margin-top: 1.5rem; width: 100%; padding: 0.8rem; background-color: #8b4513; color: #fff8e1; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">Se connecter</button>

            <p style="text-align: center; margin-top: 1.5rem;">
                Pas encore de compte ? <a href="inscription.php" style="color: #8b4513; font-weight: bold;">Crée-en un ici</a>
            </p>
        </form>
    </div>
</main>

<?php include("footer.php"); ?>
