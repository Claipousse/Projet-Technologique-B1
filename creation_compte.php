<?php
require_once(__DIR__ . '/config/config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bdd = connexionBDD();

    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $role = "participant";

    $verif = $bdd->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $verif->execute([$email]);

    if ($verif->rowCount() > 0) {
        $erreur = "Un compte existe déjà avec cet email.";
    } else {
        $requete = $bdd->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
        $requete->execute([$nom, $prenom, $email, $mot_de_passe, $role]);
        header("Location: index.php?inscription=ok");
        exit();
    }
}
?>

<?php include("includes/header.php"); ?>

<main style="padding-top: 120px; padding-bottom: 80px; background-color: #f5f5dc;">
    <div style="max-width: 600px; margin: 0 auto; background-color: white; padding: 2rem; border-radius: 8px; border: 1px solid #d2b48c; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h1 style="text-align: center; font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #8b4513;">Créer un compte</h1>

        <?php if ($erreur): ?>
            <p style="color: red; text-align: center; font-weight: bold;"><?php echo $erreur; ?></p>
        <?php endif; ?>

        <form method="post" action="">
            <label for="nom" style="font-weight: bold; display: block; margin-top: 1.2rem;">Nom</label>
            <input type="text" id="nom" name="nom" required style="width: 100%; padding: 0.8rem; border: 1px solid #d2b48c; border-radius: 4px; background-color: #f5f5dc;">

            <label for="prenom" style="font-weight: bold; display: block; margin-top: 1.2rem;">Prénom</label>
            <input type="text" id="prenom" name="prenom" required style="width: 100%; padding: 0.8rem; border: 1px solid #d2b48c; border-radius: 4px; background-color: #f5f5dc;">

            <label for="email" style="font-weight: bold; display: block; margin-top: 1.2rem;">Adresse email</label>
            <input type="email" id="email" name="email" required style="width: 100%; padding: 0.8rem; border: 1px solid #d2b48c; border-radius: 4px; background-color: #f5f5dc;">

            <label for="mot_de_passe" style="font-weight: bold; display: block; margin-top: 1.2rem;">Mot de passe</label>
            <input type="password" id="mot_de_passe" name="mot_de_passe" required style="width: 100%; padding: 0.8rem; border: 1px solid #d2b48c; border-radius: 4px; background-color: #f5f5dc;">

            <button type="submit" style="margin-top: 2rem; width: 100%; padding: 0.8rem; background-color: #8b4513; color: #fff8e1; border: none; border-radius: 4px; font-weight: bold; cursor: pointer;">S'inscrire</button>

            <p style="text-align: center; margin-top: 1.5rem;">
                Déjà un compte ? <a href="connexion.php" style="color: #8b4513; font-weight: bold;">Connecte-toi ici</a>
            </p>
        </form>
    </div>
</main>

<?php include("includes/footer.php"); ?>
