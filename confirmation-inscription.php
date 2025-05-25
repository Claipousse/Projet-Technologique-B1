<?php
require_once(__DIR__ . '/config/config.php');
$conn = connexionBDD();

$prenom = null;
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    $stmt = $conn->prepare("SELECT * FROM utilisateur WHERE email = ?");
    $stmt->execute([$email]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur) {
        // Connecter automatiquement l'utilisateur qui vient de créer son compte
        $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
        $_SESSION['role'] = $utilisateur['role'];
        $_SESSION['nom'] = $utilisateur['nom'];
        $_SESSION['prenom'] = $utilisateur['prenom'];

        $prenom = $utilisateur['prenom'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation - Pistache</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Playfair Display', serif;
            background-color: #f5f5dc;
            color: #3e2723;
        }

        .wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        main {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
        }

        .confirmation-box {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        .confirmation-box h1 {
            color: #8b4513;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .confirmation-box p {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
        }

        .confirmation-box a {
            background-color: #8b4513;
            color: white;
            padding: 0.7rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            display: inline-block;
        }

        .confirmation-box a:hover {
            background-color: #5d4037;
        }

        footer {
            text-align: center;
            padding: 1rem;
            background-color: #5d4037;
            color: #fff8e1;
        }
    </style>
</head>
<body>
<div class="wrapper">

    <?php include_once 'includes/header.php'; ?>

    <main>
        <div class="confirmation-box">
            <h1>Merci pour votre inscription !</h1>
            <?php if ($prenom): ?>
                <p>Bienvenue, <?= htmlspecialchars($prenom) ?> !</p>
            <?php endif; ?>
            <a href="index.php">Retour à l'accueil</a>
        </div>
    </main>

    <?php include_once 'includes/footer.php'; ?>

</div>
</body>
</html>