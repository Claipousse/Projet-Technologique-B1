<?php
require_once '../config/config.php';
require_once '../includes/fonctions.php';

// Vérifier si l'utilisateur est connecté
if (!estConnecte()) {
    redirigerAvecMessage('../auth/connexion.php', 'Vous devez être connecté pour effectuer cette action.', 'warning');
}

// Vérifier si la requête est en POST et contient l'ID d'inscription
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_inscription'])) {
    redirigerAvecMessage('mes-inscriptions.php', 'Demande invalide.', 'danger');
}

$id_inscription = (int)$_POST['id_inscription'];
$id_utilisateur = $_SESSION['id_utilisateur'];

try {
    $resultat = desinscrireUtilisateur($id_inscription, $id_utilisateur);
    
    if ($resultat['success']) {
        redirigerAvecMessage('mes-inscriptions.php', $resultat['message'], 'success');
    } else {
        redirigerAvecMessage('mes-inscriptions.php', $resultat['message'], 'danger');
    }
    
} catch (Exception $e) {
    redirigerAvecMessage('mes-inscriptions.php', 'Erreur lors de la désinscription : ' . $e->getMessage(), 'danger');
}