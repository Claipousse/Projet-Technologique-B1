<?php
// details.php : affiche les infos d'une inscription

// On inclut la connexion à la base et le haut de page admin
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../includes/admin-header.php';

$conn = connexionBDD(); // On se connecte à la base

// On vérifie qu'un id est passé dans l'URL
if (isset($_GET['id'])) {
    $id = (int) $_GET['id']; // On transforme en nombre entier

    // Requête pour récupérer les détails de l'inscription avec le nom, la date, le lieu, etc.
    $sql = "SELECT i.*, u.nom, u.prenom, e.nom AS nom_evenement, e.date, e.heure, e.lieu
            FROM inscriptions i
            JOIN utilisateurs u ON i.id_utilisateur = u.id_utilisateur
            JOIN evenements e ON i.id_evenement = e.id_evenement
            WHERE i.id_inscription = $id";

    $result = $conn->query($sql); // On exécute
    $inscription = $result->fetch_assoc(); // On prend le résultat sous forme de tableau
} else {
    echo "ID manquant"; // Si pas d'id, on affiche un message d'erreur
    exit();
}
?>

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

<!-- Lien pour retourner à la liste des inscriptions -->
<p><a href="liste.php">Retour</a></p>

<?php require_once __DIR__ . '/../admin-footer.php'; ?>
<?php $conn->close(); ?>
