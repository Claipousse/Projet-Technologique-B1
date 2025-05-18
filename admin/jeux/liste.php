<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

// Vérification des droits d'accès
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}

// Récupérer la liste des jeux
try {
    $conn = connexionBDD();
    $sql = "SELECT j.id_jeux, j.nom, j.annee_sortie,
                  g.nom_genre, t.nom_type 
           FROM jeux j
           JOIN genre g ON j.id_genre = g.id_genre
           JOIN type t ON j.id_type = t.id_type
           ORDER BY j.nom";
    $stmt = $conn->query($sql);
    $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur: " . $e->getMessage();
}

// Affichage du message
$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['type']) ? $_GET['type'] : 'info';

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between mb-3">
            <h1>Liste des jeux</h1>
            <a href="ajouter.php" class="btn btn-success">Ajouter un jeu</a>
        </div>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (empty($jeux)) : ?>
            <div class="alert alert-info">Aucun jeu disponible.</div>
        <?php else : ?>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Nom</th>
                    <th>Genre</th>
                    <th>Type</th>
                    <th>Année</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($jeux as $jeu) : ?>
                    <tr>
                        <td><?php echo htmlspecialchars($jeu['nom']); ?></td>
                        <td><?php echo htmlspecialchars($jeu['nom_genre']); ?></td>
                        <td><?php echo htmlspecialchars($jeu['nom_type']); ?></td>
                        <td><?php echo htmlspecialchars($jeu['annee_sortie']); ?></td>
                        <td>
                            <a href="modifier.php?id=<?php echo $jeu['id_jeux']; ?>" class="btn btn-sm btn-primary">Modifier</a>
                            <a href="suppression.php?id=<?php echo $jeu['id_jeux']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce jeu ?');">Supprimer</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php include_once '../includes/admin-footer.php'; ?>