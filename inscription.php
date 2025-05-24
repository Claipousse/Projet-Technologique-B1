<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/fonctions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$evenements = getEvenements();
$jeux = getJeux();

// Traitement du formulaire
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $nb_accompagnant = intval($_POST['nb_accompagnant'] ?? 0);
    $id_evenement = intval($_POST['id_evenement'] ?? 0);
    $jeux_selectionnes = $_POST['jeux'] ?? [];
    
    // Validation des données
    $erreurs = [];
    
    if (empty($nom)) $erreurs[] = "Le nom est requis";
    if (empty($prenom)) $erreurs[] = "Le prénom est requis";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide";
    if ($id_evenement <= 0) $erreurs[] = "Veuillez sélectionner un événement";
    if ($nb_accompagnant < 0) $erreurs[] = "Le nombre d'accompagnants ne peut pas être négatif";
    if (count($jeux_selectionnes) > 3) $erreurs[] = "Vous ne pouvez sélectionner que 3 jeux maximum";
    

    $jeux_selectionnes = array_map('intval', $jeux_selectionnes);
    $jeux_selectionnes = array_filter($jeux_selectionnes, function($id) { return $id > 0; });
    
    if (empty($erreurs)) {
        try {
            $result = inscrireUtilisateur($nom, $prenom, $email, $nb_accompagnant, $id_evenement, $jeux_selectionnes);
            
            if ($result['success']) {
                $message = $result['message'];
                $message_type = 'success';
                // Réinitialiser le formulaire
                $_POST = [];
            } else {
                $message = $result['message'];
                $message_type = 'error';
            }
            
        } catch(Exception $e) {
            $message = "Erreur lors de l'inscription : " . $e->getMessage();
            $message_type = 'error';
        }
    } else {
        $message = implode('<br>', $erreurs);
        $message_type = 'error';
    }
}
?>

<?php include("includes/header.php"); ?>


<div class="page-content">
    <main>
        <div class="auth-container">
            <h1>Inscription à un Événement</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" id="nom" name="nom" class="form-control" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>" required />
                </div>

                <div class="form-group">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" id="prenom" name="prenom" class="form-control" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>" required />
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Adresse email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                </div>

                <div class="form-group">
                    <label for="nb_accompagnant" class="form-label">Nombre d'accompagnants</label>
                    <input type="number" id="nb_accompagnant" name="nb_accompagnant" class="form-control" min="0" value="<?php echo intval($_POST['nb_accompagnant'] ?? 0); ?>" />
                </div>

                <div class="form-group">
                    <label for="jeux" class="form-label">Sélection des jeux (3 maximum)</label>
                    <div class="jeux-selection">
                        <p class="jeux-info">Vous pouvez choisir jusqu'à 3 jeux auxquels vous souhaitez participer :</p>
                        <div class="jeux-grid">
                            <?php foreach ($jeux as $jeu): ?>
                                <?php $checked = isset($_POST['jeux']) && in_array($jeu['id_jeux'], $_POST['jeux']) ? 'checked' : ''; ?>
                                <div class="jeu-item">
                                    <input type="checkbox" 
                                           id="jeu_<?php echo $jeu['id_jeux']; ?>" 
                                           name="jeux[]" 
                                           value="<?php echo $jeu['id_jeux']; ?>"
                                           <?php echo $checked; ?>
                                           class="jeu-checkbox">
                                    <label for="jeu_<?php echo $jeu['id_jeux']; ?>" class="jeu-label">
                                        <strong><?php echo htmlspecialchars($jeu['nom']); ?></strong>
                                        <?php if (!empty($jeu['description_courte'])): ?>
                                            <span class="jeu-description"><?php echo htmlspecialchars($jeu['description_courte']); ?></span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="jeux-counter" class="jeux-counter">0/3 jeux sélectionnés</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="id_evenement" class="form-label">Événement</label>
                    <select id="id_evenement" name="id_evenement" class="form-control" required onchange="showEventInfo(this.value)">
                        <option value="">-- Sélectionnez un événement --</option>
                        <?php foreach ($evenements as $evenement): ?>
                            <?php $places_disponibles = getPlacesDisponibles($evenement['id_evenement'], $evenement['capacite_max']); ?>
                            <option value="<?php echo $evenement['id_evenement']; ?>" 
                                    <?php echo (isset($_POST['id_evenement']) && $_POST['id_evenement'] == $evenement['id_evenement']) ? 'selected' : ''; ?>
                                    data-description="<?php echo htmlspecialchars($evenement['description']); ?>"
                                    data-date-debut="<?php echo date('d/m/Y', strtotime($evenement['date_debut'])); ?>"
                                    data-date-fin="<?php echo date('d/m/Y', strtotime($evenement['date_fin'])); ?>"
                                    data-duree="<?php echo $evenement['duree_type']; ?>"
                                    data-places="<?php echo $places_disponibles; ?>"
                                    <?php echo $places_disponibles <= 0 ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($evenement['titre']); ?>
                                <?php if ($places_disponibles <= 0): ?>
                                    (COMPLET)
                                <?php else: ?>
                                    (<?php echo $places_disponibles; ?> places restantes)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div id="event-info1" class="event-info1"></div>
                </div>

                <button type="submit" class="btn">S'inscrire</button>
            </form>
        </div>
    </main>
</div>

<script>
function showEventInfo(eventId) {
    const eventInfo = document.getElementById('event-info1');
    const select = document.getElementById('id_evenement');
    const selectedOption = select.options[select.selectedIndex];
    
    if (eventId && selectedOption.dataset.description) {
        const dateDebut = selectedOption.dataset.dateDebut;
        const dateFin = selectedOption.dataset.dateFin;
        const duree = selectedOption.dataset.duree;
        const places = selectedOption.dataset.places;
        const description = selectedOption.dataset.description;
        
        let dateText = dateDebut;
        if (dateDebut !== dateFin) {
            dateText += ' au ' + dateFin;
        }
        
        eventInfo.innerHTML = `
            <strong>Description :</strong> ${description}<br>
            <strong>Date :</strong> ${dateText}<br>
            <strong>Durée :</strong> ${duree}<br>
            <span class="capacity-info"><strong>Places disponibles :</strong> ${places}</span>
        `;
        eventInfo.classList.add('show');
    } else {
        eventInfo.classList.remove('show');
    }
}

// Gestion de la sélection des jeux (maximum 3)
function updateJeuxCounter() {
    const checkboxes = document.querySelectorAll('.jeu-checkbox');
    const counter = document.getElementById('jeux-counter');
    const selectedCount = document.querySelectorAll('.jeu-checkbox:checked').length;
    
    counter.textContent = `${selectedCount}/3 jeux sélectionnés`;
    counter.classList.toggle('limit-reached', selectedCount >= 3);
    
    // Désactiver les autres checkboxes si 3 sont sélectionnées
    checkboxes.forEach(checkbox => {
        const jeuItem = checkbox.closest('.jeu-item');
        if (!checkbox.checked && selectedCount >= 3) {
            checkbox.disabled = true;
            jeuItem.style.opacity = '0.5';
        } else {
            checkbox.disabled = false;
            jeuItem.style.opacity = '1';
        }
        
        jeuItem.classList.toggle('selected', checkbox.checked);
    });
}

// Afficher les infos de l'événement sélectionné au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('id_evenement');
    if (select.value) {
        showEventInfo(select.value);
    }
    
    updateJeuxCounter();
    
    const checkboxes = document.querySelectorAll('.jeu-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateJeuxCounter);
    });
});
</script>

<?php include("includes/footer.php"); ?>