<?php
require_once __DIR__ . '/../../config/config.php';

$pdo = connexionBDD();

// Récupération des événements à venir
$requete_evenements = $pdo->query("SELECT id_evenement, titre, date_debut, date_fin FROM evenement WHERE date_debut > CURDATE() ORDER BY date_debut");
$evenements = $requete_evenements->fetchAll(PDO::FETCH_ASSOC);

// Récupération des utilisateurs
$requete_utilisateurs = $pdo->query("SELECT id_utilisateur, nom, prenom, email FROM utilisateur ORDER BY nom, prenom");
$utilisateurs = $requete_utilisateurs->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Données du formulaire
    $id_utilisateur = $_POST['id_utilisateur'];
    $id_evenement = $_POST['id_evenement'];
    $nb_accompagnant = $_POST['nb_accompagnant'];
    $date_inscription = date('Y-m-d');

    // Vérifier si l'e-mail est valide
    $requete_email = $pdo->prepare("SELECT email FROM utilisateur WHERE id_utilisateur = ?");
    $requete_email->execute([$id_utilisateur]);
    $email_utilisateur = $requete_email->fetchColumn();

    if (!$email_utilisateur || !filter_var($email_utilisateur, FILTER_VALIDATE_EMAIL)) {
        echo "Inscription impossible : l'adresse e-mail est invalide.";
        exit;
    }

    // Vérifier si déjà inscrit
    $requete_existe = $pdo->prepare("SELECT id_inscription FROM inscription WHERE id_utilisateur = ? AND id_evenement = ?");
    $requete_existe->execute([$id_utilisateur, $id_evenement]);
    $existe = $requete_existe->fetch();

    if ($existe) {
        echo "Inscription impossible : vousn êtes déjà inscrit à cet événement.";
    } else {
        // Si tout est bon : on valide directement avec validation auto, pas manuellement!!
        $status = 'validé';
        $requete = $pdo->prepare("INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status) VALUES (?, ?, ?, ?, ?)");
        $requete->execute([$id_utilisateur, $id_evenement, $nb_accompagnant, $date_inscription, $status]);

        echo "Votre inscription a été enregistrée.";
    }
}
?>
