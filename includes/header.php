<?php
// Démarrer la session si pas déjà fait
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/fonctions.php';

// Utiliser la fonction pour déterminer le chemin de base
$basePath = getBasePath();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pistache - Boutique de Jeux de Société</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lora:wght@400;500&display=swap">
    <link rel="stylesheet" href="<?php echo $basePath; ?>assets/css/style.css">
</head>
<body>
<header>
    <a href="<?php echo $basePath; ?>index.php" class="logo">
        <img src="<?php echo $basePath; ?>assets/images/pistache-logo.png" alt="Logo Pistache" />
        Pistache
    </a>
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    <nav>
        <ul id="mainMenu">
            <li><a href="<?php echo $basePath; ?>index.php">Accueil</a></li>
            <li><a href="<?php echo $basePath; ?>catalogue.php">Catalogue</a></li>
            <li><a href="<?php echo $basePath; ?>evenements.php">Événements</a></li>
            <?php if (estConnecte()): ?>
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <?php echo isset($_SESSION['prenom']) ? $_SESSION['prenom'] : 'Utilisateur'; ?>
                        <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="dropdown-content">
                        <a href="<?php echo $basePath; ?>user/mes-inscriptions.php"><i class="fas fa-calendar-check"></i> Mes inscriptions</a>
                        <?php if (estAdmin()): ?>
                            <a href="<?php echo $basePath; ?>admin/index.php"><i class="fas fa-cog"></i> Administration</a>
                        <?php endif; ?>
                        <a href="<?php echo $basePath; ?>auth/deconnexion.php"><i class="fas fa-sign-out-alt"></i> Se déconnecter</a>
                    </div>
                </li>
            <?php else: ?>
                <li><a href="<?php echo $basePath; ?>auth/connexion.php">Se connecter</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>