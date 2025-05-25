<?php
require_once(__DIR__ . '/../config/config.php');
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lora:wght@400;500&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="wrapper">

    <?php include_once '../includes/header.php'; ?>

    <main>
        <div class="confirmation-box">
            <h1>Merci pour votre inscription !</h1>
            <?php if ($prenom): ?>
                <p>Bienvenue, <?= htmlspecialchars($prenom) ?> !</p>
            <?php endif; ?>
            <a href="../index.php">Retour à l'accueil</a>
        </div>
    </main>

    <?php include_once '../includes/footer.php'; ?>

</div>
</body>
</html>