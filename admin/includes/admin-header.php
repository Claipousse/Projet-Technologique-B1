<?php
// Détecter le niveau de profondeur pour les chemins relatifs
$currentPath = $_SERVER['REQUEST_URI'];
$pathDepth = substr_count(trim($currentPath, '/'), '/');

// Définir le chemin de base selon la profondeur
if (strpos($currentPath, '/admin/jeux/') !== false || strpos($currentPath, '/admin/evenements/') !== false) {
    $basePath = '../';
} else {
    $basePath = '';
}
?>
<!DOCTYPE html>
<html lang="fr" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- CSS personnalisé d'administration -->
    <link rel="stylesheet" href="<?php echo $basePath; ?>../assets/css/admin.css">
    <title>Administration - Pistache</title>
</head>
<body class="d-flex flex-column h-100">
<!-- Barre de navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $basePath; ?>index.php">
            <i class="bi bi-speedometer2"></i> Administration
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>index.php">
                        <i class="bi bi-house"></i> Tableau de bord
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>jeux/liste.php">
                        <i class="bi bi-controller"></i> Jeux
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>evenements/liste.php">
                        <i class="bi bi-calendar-event"></i> Événements
                    </a>
                </li>
            </ul>
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>../index.php">
                                <i class="bi bi-arrow-left"></i> Retour au site
                            </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>../deconnexion.php">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="bg-light border-bottom">
    <div class="container py-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item">
                    <a href="<?php echo $basePath; ?>index.php" class="text-decoration-none">Administration</a>
                </li>
                <?php
                // Générer le breadcrumb automatiquement selon l'URL
                if (strpos($currentPath, '/jeux/') !== false) {
                    echo '<li class="breadcrumb-item"><a href="' . $basePath . 'jeux/liste.php" class="text-decoration-none">Jeux</a></li>';
                    if (strpos($currentPath, 'ajouter.php') !== false) {
                        echo '<li class="breadcrumb-item active">Ajouter</li>';
                    } elseif (strpos($currentPath, 'modifier.php') !== false) {
                        echo '<li class="breadcrumb-item active">Modifier</li>';
                    } elseif (strpos($currentPath, 'liste.php') !== false) {
                        echo '<li class="breadcrumb-item active">Liste</li>';
                    }
                } elseif (strpos($currentPath, '/evenements/') !== false) {
                    echo '<li class="breadcrumb-item"><a href="' . $basePath . 'evenements/liste.php" class="text-decoration-none">Événements</a></li>';
                    if (strpos($currentPath, 'ajouter.php') !== false) {
                        echo '<li class="breadcrumb-item active">Ajouter</li>';
                    } elseif (strpos($currentPath, 'modifier.php') !== false) {
                        echo '<li class="breadcrumb-item active">Modifier</li>';
                    } elseif (strpos($currentPath, 'liste.php') !== false) {
                        echo '<li class="breadcrumb-item active">Liste</li>';
                    }
                } else {
                    echo '<li class="breadcrumb-item active">Tableau de bord</li>';
                }
                ?>
            </ol>
        </nav>
    </div>
</div>

<!-- Début du contenu principal -->
<main class="container mt-4 flex-grow-1">