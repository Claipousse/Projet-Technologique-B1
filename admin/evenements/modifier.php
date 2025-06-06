<?php
require_once '../../config/config.php';
require_once '../../includes/fonctions.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

// Initialisation des variables
$id_evenement = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';
$messageType = '';
$erreurs = [];
$evenement = null;

// Récupérer les informations de l'événement
$conn = connexionBDD();

// Récupération de l'événement
$stmt = $conn->prepare('SELECT * FROM evenement WHERE id_evenement = ?');
$stmt->execute([$id_evenement]);
$evenement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evenement) {
    header("Location: liste.php?message=" . urlencode("Événement introuvable"));
    exit;
}

// Récupération des jeux associés à l'événement
$stmt = $conn->prepare('SELECT id_jeux FROM jeux_evenement WHERE id_evenement = ?');
$stmt->execute([$id_evenement]);
$jeuxSelectionnes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Récupération de tous les jeux
$stmt = $conn->query("SELECT id_jeux, nom FROM jeux ORDER BY nom");
$jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $date_debut = $_POST['date_debut'];
    $duree_type = $_POST['duree_type'];
    $capacite_max = $_POST['capacite_max'];
    $jeux = isset($_POST['jeux']) ? $_POST['jeux'] : [];

    // Calculer automatiquement la date de fin selon la durée
    $date_fin = $date_debut; // Par défaut pour demi-journée et journée
    if ($duree_type === 'weekend') {
        $date_fin = date('Y-m-d', strtotime($date_debut . ' +1 day'));
    }

    // Validation simple
    if (empty($titre)) $erreurs[] = "Le titre est obligatoire.";
    if (empty($description)) $erreurs[] = "La description est obligatoire.";
    if (empty($date_debut)) $erreurs[] = "La date de début est obligatoire.";
    if (empty($duree_type)) $erreurs[] = "La durée est obligatoire.";
    if ($capacite_max <= 0) $erreurs[] = "La capacité doit être positive.";

    // Validation : date de début >= date actuelle (sauf si l'événement a déjà commencé)
    $date_actuelle = date('Y-m-d');
    if ($date_debut < $date_actuelle && $evenement['date_debut'] >= $date_actuelle) {
        $erreurs[] = "La date de début doit être supérieure ou égale à la date actuelle.";
    }

    if (empty($erreurs)) {
        $conn->beginTransaction();

        // Mise à jour de l'événement
        $sql = "UPDATE evenement SET titre = ?, description = ?, date_debut = ?, date_fin = ?, capacite_max = ?, duree_type = ? WHERE id_evenement = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$titre, $description, $date_debut, $date_fin, $capacite_max, $duree_type, $id_evenement]);

        // Mise à jour des jeux associés
        $stmt = $conn->prepare("DELETE FROM jeux_evenement WHERE id_evenement = ?");
        $stmt->execute([$id_evenement]);

        if (!empty($jeux)) {
            $stmt = $conn->prepare("INSERT INTO jeux_evenement (id_evenement, id_jeux) VALUES (?, ?)");
            foreach ($jeux as $id_jeux) {
                $stmt->execute([$id_evenement, $id_jeux]);
            }
        }

        $conn->commit();
        header("Location: liste.php?message=" . urlencode("Événement modifié avec succès"));
        exit;
    } else {
        $message = implode("<br>", $erreurs);
        $messageType = "danger";
    }
}

include_once '../includes/admin-header.php';
?>

    <div class="container mt-4">
        <h1>Modifier un événement</h1>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label for="titre" class="form-label">Titre *</label>
                <input type="text" class="form-control" id="titre" name="titre" value="<?php echo htmlspecialchars($evenement['titre']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description *</label>
                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($evenement['description']); ?></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="date_debut" class="form-label">Date de début *</label>
                    <input type="date" class="form-control" id="date_debut" name="date_debut"
                           value="<?php echo $evenement['date_debut']; ?>"
                           min="<?php echo ($evenement['date_debut'] < date('Y-m-d')) ? $evenement['date_debut'] : date('Y-m-d'); ?>"
                           required>
                    <?php if ($evenement['date_debut'] >= date('Y-m-d')) : ?>
                        <div class="form-text">La date doit être supérieure ou égale à aujourd'hui</div>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="capacite_max" class="form-label">Capacité *</label>
                    <input type="number" class="form-control" id="capacite_max" name="capacite_max" min="1" value="<?php echo $evenement['capacite_max']; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="duree_type" class="form-label">Durée *</label>
                <select class="form-select" id="duree_type" name="duree_type" required>
                    <option value="demi-journée" <?php if ($evenement['duree_type'] == 'demi-journée') echo 'selected'; ?>>Demi-journée</option>
                    <option value="journée" <?php if ($evenement['duree_type'] == 'journée') echo 'selected'; ?>>Journée</option>
                    <option value="weekend" <?php if ($evenement['duree_type'] == 'weekend') echo 'selected'; ?>>Weekend</option>
                </select>
                <div class="form-text" id="date_fin_info"></div>
            </div>

            <div class="mb-3">
                <label class="form-label">Jeux associés</label>
                <div class="row row-cols-1 row-cols-md-3 g-2">
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

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="liste.php" class="btn btn-secondary">Annuler</a>
            </div>
        </form>
    </div>

<?php include_once '../includes/admin-footer.php'; ?>