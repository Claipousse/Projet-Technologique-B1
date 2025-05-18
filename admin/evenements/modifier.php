<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

// Vérification des droits d'accès
if (!estConnecte() || !estAdmin()) {
    redirigerAvecMessage('../../connexion.php', "Vous devez être connecté en tant qu'administrateur.");
}

// Initialisation des variables
$id_evenement = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$messageType = '';
$erreurs = [];
$evenement = null;

// Récupérer les informations de l'événement
try {
    $conn = connexionBDD();
    
    // Récupération de l'événement
    $stmt = $conn->prepare('SELECT * FROM evenement WHERE id_evenement = :id');
    $stmt->bindParam(':id', $id_evenement);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        redirigerAvecMessage('liste.php', "Cet événement n'existe pas.", 'danger');
    }
    
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupération des jeux associés à l'événement
    $stmt = $conn->prepare('SELECT id_jeux FROM jeux_evenement WHERE id_evenement = :id_evenement');
    $stmt->bindParam(':id_evenement', $id_evenement);
    $stmt->execute();
    $jeuxSelectionnes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des données : " . $e->getMessage();
    $messageType = "danger";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $capacite_max = $_POST['capacite_max'];
    $duree_type = $_POST['duree_type'];
    $jeux = isset($_POST['jeux']) ? $_POST['jeux'] : [];
    
    // Validation des données
    if (estVide($titre)) $erreurs[] = "Le titre de l'événement est obligatoire.";
    if (estVide($description)) $erreurs[] = "La description est obligatoire.";
    if (estVide($date_debut)) $erreurs[] = "La date de début est obligatoire.";
    if (estVide($date_fin)) $erreurs[] = "La date de fin est obligatoire.";
    if ($date_fin < $date_debut) $erreurs[] = "La date de fin doit être postérieure ou égale à la date de début.";
    if (!is_numeric($capacite_max) || $capacite_max <= 0) $erreurs[] = "La capacité maximale doit être un nombre positif.";

    // Vérifier si un événement avec les mêmes dates existe déjà (hors celui en cours de modification)
    if (evenementExiste($date_debut, $date_fin, $id_evenement)) {
        $erreurs[] = "Un autre événement est déjà programmé pour ces dates.";
    }
    
    // Si tout est OK, on modifie l'événement
    if (empty($erreurs)) {
        try {
            $conn = connexionBDD();
            $conn->beginTransaction();
            
            // Mise à jour des données de l'événement
            $sql = "UPDATE evenement 
                    SET titre = :titre,
                        description = :description,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        capacite_max = :capacite_max,
                        duree_type = :duree_type
                    WHERE id_evenement = :id_evenement";
                    
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':date_debut' => $date_debut,
                ':date_fin' => $date_fin,
                ':capacite_max' => $capacite_max,
                ':duree_type' => $duree_type,
                ':id_evenement' => $id_evenement
            ]);
            
            // Supprimer les associations actuelles avec les jeux
            $stmt = $conn->prepare("DELETE FROM jeux_evenement WHERE id_evenement = :id_evenement");
            $stmt->execute([':id_evenement' => $id_evenement]);
            
            // Ajouter les nouvelles associations avec les jeux
            if (!empty($jeux)) {
                $insertJeuxQuery = "INSERT INTO jeux_evenement (id_evenement, id_jeux) VALUES (:id_evenement, :id_jeux)";
                $stmt = $conn->prepare($insertJeuxQuery);
                
                foreach ($jeux as $id_jeux) {
                    $stmt->execute([
                        ':id_evenement' => $id_evenement,
                        ':id_jeux' => $id_jeux
                    ]);
                }
            }
            
            $conn->commit();
            
            redirigerAvecMessage('liste.php', "L'événement a été modifié avec succès.", "success");
            
        } catch (PDOException $e) {
            $conn->rollBack();
            $message = "Erreur lors de la modification de l'événement : " . $e->getMessage();
            $messageType = "danger";
        }
    } else {
        $message = "Des erreurs ont été détectées :<br>" . implode("<br>", $erreurs);
        $messageType = "danger";
    }
}

// Récupération de tous les jeux pour le menu déroulant
try {
    $conn = connexionBDD();
    $stmt = $conn->query("SELECT id_jeux, nom FROM jeux ORDER BY nom");
    $jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des jeux : " . $e->getMessage();
    $messageType = "danger";
}

include_once '../includes/admin-header.php';
?>

<div class="container mt-4">
    <h1>Modifier un événement</h1>
    
    <?php if (!empty($message)) : ?>
        <div class="alert alert-<?php echo $messageType; ?>" role="alert">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($evenement) : ?>
        <form method="post">
            <div class="mb-3">
                <label for="titre" class="form-label">Titre de l'événement *</label>
                <input type="text" class="form-control" id="titre" name="titre" value="<?php echo htmlspecialchars($evenement['titre']); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Description *</label>
                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($evenement['description']); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_debut" class="form-label">Date de début *</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut" value="<?php echo $evenement['date_debut']; ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="date_fin" class="form-label">Date de fin *</label>
                    <input type="date" class="form-control" id="date_fin" name="date_fin" value="<?php echo $evenement['date_fin']; ?>" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="capacite_max" class="form-label">Capacité maximale *</label>
                    <input type="number" class="form-control" id="capacite_max" name="capacite_max" min="1" value="<?php echo htmlspecialchars($evenement['capacite_max']); ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="duree_type" class="form-label">Type de durée *</label>
                    <select class="form-select" id="duree_type" name="duree_type" required>
                        <option value="demi-journée" <?php if ($evenement['duree_type'] == 'demi-journée') echo 'selected'; ?>>Demi-journée</option>
                        <option value="journée" <?php if ($evenement['duree_type'] == 'journée') echo 'selected'; ?>>Journée</option>
                        <option value="weekend" <?php if ($evenement['duree_type'] == 'weekend') echo 'selected'; ?>>Weekend</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Jeux associés à l'événement</label>
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <?php foreach ($jeux as $jeu) : ?>
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="jeux[]" value="<?php echo $jeu['id_jeux']; ?>" id="jeu_<?php echo $jeu['id_jeux']; ?>"
                                    <?php if (in_array($jeu['id_jeux'], $jeuxSelectionnes)) echo 'checked'; ?>>
                                <label class="form-check-label" for="jeu_<?php echo $jeu['id_jeux']; ?>">
                                    <?php echo htmlspecialchars($jeu['nom']); ?>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="mb-3 mt-4">
                <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <a href="liste.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    <?php else : ?>
        <div class="alert alert-danger">
            Impossible de charger les données de l'événement.
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Assurer que la date de fin est au moins égale à la date de début
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    
    dateDebut.addEventListener('change', function() {
        if (dateFin.value && dateFin.value < dateDebut.value) {
            dateFin.value = dateDebut.value;
        }
        dateFin.min = dateDebut.value;
    });
    
    // Initialiser la date minimale pour la date de fin
    dateFin.min = dateDebut.value;
});
</script>

<?php include_once '../includes/admin-footer.php'; ?>