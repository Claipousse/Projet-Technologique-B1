<?php
session_start();
require_once(__DIR__ . '/config/config.php');

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
            header("Location: ../index.php");
        } else {
            header("Location: ../index.html");
        }
        exit();
    } else {
        $erreur = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Connexion - Pistache</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lora:wght@400;500&display=swap"/>
    <style>
        :root {
            --primary-color: #8b4513;
            --secondary-color: #5d4037;
            --accent-color: #d2b48c;
            --light-bg: #f5f5dc;
            --dark-text: #3e2723;
            --light-text: #fff8e1;
        }

        body {
            margin: 0;
            padding: 0;
            background-color: var(--light-bg);
            font-family: 'Lora', Georgia, serif;
            color: var(--dark-text);
        }

        .container {
            max-width: 500px;
            margin: 6rem auto;
            padding: 2rem;
            background-color: white;
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-family: "Playfair Display", serif;
            font-size: 2rem;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 2rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--accent-color);
            border-radius: 4px;
            margin-bottom: 1.5rem;
            background-color: var(--light-bg);
        }

        button {
            width: 100%;
            padding: 0.8rem;
            background-color: var(--primary-color);
            color: var(--light-text);
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: var(--secondary-color);
        }

        .erreur {
            color: red;
            text-align: center;
            margin-bottom: 1rem;
        }

        .logo {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .logo img {
            height: 60px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="../media/images/pistache-logo.png" alt="Logo Pistache">
    </div>
    <h1>Connexion</h1>
    <?php if ($erreur): ?>
        <div class="erreur"><?php echo $erreur; ?></div>
    <?php endif; ?>
    <form method="post" action="">
        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email" required>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe" name="mot_de_passe" required>

        <button type="submit">Se connecter</button>
    </form>
</div>
</body>
</html>
