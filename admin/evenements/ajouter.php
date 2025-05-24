<?php
require_once(__DIR__ . "/../../config/config.php");
$pdo = connexionBDD();

// Vérifier que l'utilisateur est connecté et est admin
if (!estConnecte() || !estAdmin()) {
    rediriger('../../connexion.php');
}

// Récupération de tous les jeux
$stmt = $pdo->query("SELECT id_jeux, nom FROM jeux ORDER BY nom");
$jeux = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = htmlspecialchars($_POST['titre']);
    $description = htmlspecialchars($_POST['description']);
    $date_debut = $_POST['date_debut'];
    $duree_type = htmlspecialchars($_POST['duree_type']);
    $capacite = intval($_POST['capacite_max']);
    $jeux_selectionnes = isset($_POST['jeux']) ? $_POST['jeux'] : [];

    // Calculer automatiquement la date de fin selon la durée
    $date_fin = $date_debut; // Par défaut pour demi-journée et journée
    if ($duree_type === 'weekend') {
        $date_fin = date('Y-m-d', strtotime($date_debut . ' +1 day'));
    }

    // Validation : date de début >= date actuelle
    $date_actuelle = date('Y-m-d');
    if ($date_debut < $date_actuelle) {
        $message = "La date de début doit être supérieure ou égale à la date actuelle.";
        $messageType = "danger";
    } else {
        try {
            $pdo->beginTransaction();

            // Insérer l'événement
            $sql = "INSERT INTO evenement (titre, description, date_debut, date_fin, capacite_max, duree_type)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$titre, $description, $date_debut, $date_fin, $capacite, $duree_type]);

            $id_evenement = $pdo->lastInsertId();

            // Insérer les jeux associés
            if (!empty($jeux_selectionnes)) {
                $stmt_jeux = $pdo->prepare("INSERT INTO jeux_evenement (id_evenement, id_jeux) VALUES (?, ?)");
                foreach ($jeux_selectionnes as $id_jeux) {
                    $stmt_jeux->execute([$id_evenement, $id_jeux]);
                }
            }

            $pdo->commit();
            $message = "✅ Événement ajouté avec succès !";
            $messageType = "success";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Erreur lors de l'ajout : " . $e->getMessage();
            $messageType = "danger";
        }
    }
}
include_once '../includes/admin-header.php';
?>

    <main class="container py-4">
        <h1 class="text-center mb-4" style="font-family: 'Playfair Display', serif; color: #8B4513;">
            Ajouter un événement
        </h1>

        <?php if (!empty($message)) : ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm p-4">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="titre" class="form-label">Titre de l'évènement</label>
                            <input type="text" class="form-control" id="titre" name="titre" required />
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="date_debut" class="form-label">Date de début</label>
                            <input type="date" class="form-control" id="date_debut" name="date_debut"
                                   min="<?php echo date('Y-m-d'); ?>" required />
                            <div class="form-text">La date doit être supérieure ou égale à aujourd'hui</div>
                        </div>

                        <div class="mb-3">
                            <label for="duree_type" class="form-label">Durée</label>
                            <select class="form-select" id="duree_type" name="duree_type" required>
                                <option value="">Choisissez une durée</option>
                                <option value="demi-journée">Demi-journée</option>
                                <option value="journée">Journée</option>
                                <option value="weekend">Weekend</option>
                            </select>
                            <div class="form-text" id="date_fin_info"></div>
                        </div>

                        <div class="mb-3">
                            <label for="capacite_max" class="form-label">Capacité maximale</label>
                            <input type="number" class="form-control" id="capacite_max" name="capacite_max" min="1" required />
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Jeux associés</label>
                            <div class="form-text mb-2">Sélectionnez les jeux qui seront disponibles lors de cet événement</div>
                            <?php if (!empty($jeux)): ?>
                                <div class="row row-cols-1 row-cols-md-3 g-2">
                                    <?php foreach ($jeux as $jeu) : ?>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="jeux[]" value="<?php echo $jeu['id_jeux']; ?>" id="jeu_<?php echo $jeu['id_jeux']; ?>">
                                                <label class="form-check-label" for="jeu_<?php echo $jeu['id_jeux']; ?>">
                                                    <?php echo htmlspecialchars($jeu['nom']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i> Aucun jeu disponible. Veuillez d'abord ajouter des jeux dans la section "Gestion des jeux".
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Ajouter l'évènement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

<?php include_once "../includes/admin-footer.php" ?>