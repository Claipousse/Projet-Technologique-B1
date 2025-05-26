<?php
require_once(__DIR__ . '/../config/config.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$erreur = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bdd = connexionBDD();
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mot_de_passe = $_POST['mot_de_passe'];
    $role = "participant";
 
    //verif email
    $verif = $bdd->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $verif->execute([$email]);

    if ($verif->rowCount() > 0) {
        $erreur = "Un compte existe déjà avec cet email.";
    } else {
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
        $requete = $bdd->prepare("INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
        $requete->execute([$nom, $prenom, $email, $mot_de_passe_hash, $role]);

        // Redirection vers confirmation avec l’e-mail en GET (ajout unique)
        header("Location: confirmation-inscription.php?email=" . urlencode($email));
        exit();
    }
  }
?>

<?php include("../includes/header.php"); ?>

<div class="page-content">
    <main>
        <div class="auth-container">
            <h1>Créer un compte</h1>

            <?php if ($erreur): ?>
                <div class="alert alert-error">
                    <?php echo $erreur; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="form-group">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" id="nom" name="nom" class="form-control" 
                     value="<?php echo isset($_POST['nom']) ? htmlspecialchars($_POST['nom']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" id="prenom" name="prenom" class="form-control" 
                     value="<?php echo isset($_POST['prenom']) ? htmlspecialchars($_POST['prenom']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                     value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <div class="password-input-container">
                        <input type="password" id="mot_de_passe" name="mot_de_passe" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('mot_de_passe')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmer_mot_de_passe" class="form-label">Confirmer le mot de passe</label>
                    <div class="password-input-container">
                        <input type="password" id="confirmer_mot_de_passe" name="confirmer_mot_de_passe" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmer_mot_de_passe')">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn">Créer un compte</button>

                <p>
                    Déjà un compte ? <a href="connexion.php">Connecte-toi ici</a>
                </p>
            </form>
        </div>
    </main>
</div>

<?php include("../includes/footer.php"); ?>