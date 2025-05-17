<?php
require_once 'config/config.php';
require_once 'includes/fonctions.php';

$erreurs = [];
$email = '';

// Si l'utilisateur est déjà connecté, le rediriger vers la page d'accueil
if (estConnecte()) {
    // Si c'est un admin, rediriger vers le tableau de bord admin
    if (estAdmin()) {
        header('Location: admin/index.php');
        exit;
    }
    // Sinon rediriger vers la page d'accueil
    header('Location: index.php');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $mot_de_passe = isset($_POST['mot_de_passe']) ? $_POST['mot_de_passe'] : '';

    // Validation des données
    if (empty($email)) {
        $erreurs[] = "L'adresse email est obligatoire.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "L'adresse email n'est pas valide.";
    }

    if (empty($mot_de_passe)) {
        $erreurs[] = "Le mot de passe est obligatoire.";
    }

    // Si pas d'erreurs, tentative de connexion
    if (empty($erreurs)) {
        try {
            $conn = connexionBDD();
            $stmt = $conn->prepare("SELECT id_utilisateur, email, mot_de_passe, nom, prenom, role FROM utilisateur WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

                // Vérification du mot de passe
                if (password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                    // Connexion réussie
                    $_SESSION['utilisateur'] = [
                        'id' => $utilisateur['id_utilisateur'],
                        'email' => $utilisateur['email'],
                        'nom' => $utilisateur['nom'],
                        'prenom' => $utilisateur['prenom'],
                        'role' => $utilisateur['role']
                    ];

                    // Redirection selon le rôle
                    if ($utilisateur['role'] === 'admin') {
                        header('Location: admin/index.php');
                        exit;
                    } else {
                        header('Location: index.php');
                        exit;
                    }
                } else {
                    $erreurs[] = "Mot de passe incorrect.";
                }
            } else {
                $erreurs[] = "Aucun utilisateur trouvé avec cette adresse email.";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur de connexion à la base de données: " . $e->getMessage();
        }
    }
}

// Inclure l'en-tête
include_once 'includes/header.php';
?>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center">Connexion</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($erreurs)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($erreurs as $erreur): ?>
                                        <li><?php echo $erreur; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['message'])): ?>
                            <div class="alert alert-<?php echo isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info'; ?>">
                                <?php echo $_SESSION['message']; ?>
                                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="connexion.php">
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                                <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Se connecter</button>
                            </div>
                        </form>

                        <div class="mt-3 text-center">
                            <!--Lien vers des pages à ajouter dans le futur-->
                            <p>Vous n'avez pas de compte ? <a href="inscription.php">Inscrivez-vous ici</a></p>
                            <p><a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include_once 'includes/footer.php'; ?>