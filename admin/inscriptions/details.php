<?php
// details.php : affiche les infos d'une inscription précise (utilisé par l'admin)

// On inclut les fichiers pour se connecter à la base et afficher le haut de page admin
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../admin-header.php';

// On se connecte à la base de données
$conn = connexionBDD();

// On vérifie si un id d'inscription a été passé dans l'URL
if (isset($_GET['id'])) {
    // On transforme l'id reçu en entier (par sécurité)
    $id = (int) $_GET['id'];

    // Requête SQL pour récupérer les infos complètes d'une inscription
    // On joint les tables utilisateurs et événements pour avoir tous les détails
    $sql = "SELECT i.*, u.nom, u.prenom, e.nom AS nom_evenement, e.date, e.heure, e.lieu
            FROM inscriptions i
            JOIN utilisateurs u ON i.id_utilisateur = u.id_utilisateur
            JOIN evenements e ON i.id_evenement = e.id_evenement
            WHERE i.id_inscription = $id";

    // On exécute la requête
    $result = $conn->query($sql);

    // On récupère les résultats sous forme de tableau associatif (clef => valeur)
    $inscription = $result->fetch_assoc();
} else {
    // Si aucun id n'est fourni, on affiche un message d'erreur et on arrête le script
    echo "ID manquant";
    exit();
}
?>

<!-- Titre de la page -->
<h1>Détails de l'inscription</h1>

<!-- On affiche les informations de l'inscription sous forme de liste -->
<ul>
    <li><strong>Nom :</strong> <?= $inscription['nom'] ?></li>
    <li><strong>Prénom :</strong> <?= $inscription['prenom'] ?></li>
    <li><strong>Événement :</strong> <?= $inscription['nom_evenement'] ?></li>
    <li><strong>Date :</strong> <?= $inscription['date'] ?></li>
    <li><strong>Heure :</strong> <?= $inscription['heure'] ?></li>
    <li><strong>Lieu :</strong> <?= $inscription['lieu'] ?></li>
    <li><strong>Statut :</strong> <?= $inscription['statut'] ?></li>
</ul>

<!-- Lien pour revenir à la page liste.php -->
<p><a href="liste.php">Retour</a></p>

<?php
// On inclut le bas de page admin
require_once __DIR__ . '/../admin-footer.php';

// On ferme la connexion à la base de données
$conn->close();
?>