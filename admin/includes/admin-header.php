<?php
// Définir le chemin de base selon la profondeur
if (strpos($_SERVER['REQUEST_URI'], '/admin/jeux/') !== false ||
    strpos($_SERVER['REQUEST_URI'], '/admin/evenements/') !== false ||
    strpos($_SERVER['REQUEST_URI'], '/admin/inscriptions/') !== false) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo $basePath; ?>../assets/css/admin.css">
    <title>Administration - Pistache</title>
</head>
<body class="d-flex flex-column h-100">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $basePath; ?>index.php">
            <i class="bi bi-speedometer2"></i> Administration
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarAdmin">
            <ul class="navbar-nav me-auto">
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
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $basePath; ?>inscriptions/liste.php">
                        <i class="bi bi-people"></i> Inscriptions
                    </a>
                </li>
            </ul>

            <div class="navbar-nav">
                <div class="nav-item dropdown admin-dropdown">
                    <button class="nav-link btn-admin-dropdown"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                        <i class="bi bi-person-circle"></i> Admin
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>../index.php">
                                <i class="bi bi-arrow-left"></i> Retour au site
                            </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo $basePath; ?>../auth/deconnexion.php">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Contenu principal -->
<main class="container mt-4 flex-grow-1">