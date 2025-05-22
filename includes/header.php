<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pistache - Boutique de Jeux de Société</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lora:wght@400;500&display=swap">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<header>
    <a href="../index.php" class="logo">
        <img src="../assets/images/pistache-logo.png" alt="Logo Pistache" />
        Pistache
    </a>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    <nav>
        <ul id="mainMenu">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="catalogue.php">Catalogue</a></li>
            <li><a href="evenements.php">Événements</a></li>
            <li><a href="contact.php">Contact</a></li>
            <?php if (estConnecte()): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <?php echo isset($_SESSION['utilisateur']['prenom']) ? $_SESSION['utilisateur']['prenom'] : 'Utilisateur'; ?>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <?php if (estAdmin()): ?>
                            <a href="admin/index.php"><i class="fas fa-cog"></i> Administration</a>
                        <?php endif; ?>
                        <a href="profil.php"><i class="fas fa-user"></i> Mon profil</a>
                        <a href="deconnexion.php"><i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="connexion.php">Se connecter</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>