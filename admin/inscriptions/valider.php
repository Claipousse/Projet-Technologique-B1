<?php
require_once __DIR__ . '/../../config/config.php';

$pdo = connexionBDD();

// Récupération des événements a venir
$requete_evenements = $pdo->query("SELECT id_evenement, titre, date_debut, date_fin FROM evenement WHERE date_debut > CURDATE() ORDER BY date_debut");
$evenements = $requete_evenements->fetchAll(PDO::FETCH_ASSOC);

// Récupération des utilisateurs 
$requete_utilisateurs = $pdo->query("SELECT id_utilisateur, nom, prenom, email FROM utilisateur ORDER BY nom, prenom");
$utilisateurs = $requete_utilisateurs->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données d'inscription
    $id_utilisateur = $_POST['id_utilisateur'];
    $id_evenement = $_POST['id_evenement'];
    $nb_accompagnant = $_POST['nb_accompagnant'];
    $date_inscription = date('Y-m-d'); //  jour
    $status = 'en attente'; 
    
    try {
        // Vérification capacité_max
        $requete_capacite = $pdo->prepare("
            SELECT e.capacite_max, 
                   (SELECT COALESCE(SUM(nb_accompagnant), 0) + COUNT(*) 
                    FROM inscription 
                    WHERE id_evenement = ? AND status = 'validé') as inscrits_total
            FROM evenement e 
            WHERE e.id_evenement = ?");
        $requete_capacite->execute([$id_evenement, $id_evenement]);
        $capacite_info = $requete_capacite->fetch(PDO::FETCH_ASSOC);
        
        // Vérification déjà inscrit ?
        $requete_existe = $pdo->prepare("SELECT id_inscription FROM inscription WHERE id_utilisateur = ? AND id_evenement = ?");
        $requete_existe->execute([$id_utilisateur, $id_evenement]);
        $existe = $requete_existe->fetch();
        
        if ($existe) {
            echo " inscription impossible, Cet utilisateur est déjà inscrit à cet événement.";
        } 
        // Vérification dépasse capacité max ?
        elseif (isset($capacite_info['inscrits_total']) && 
                ($capacite_info['inscrits_total'] + $nb_accompagnant + 1) > $capacite_info['capacite_max']) {
            echo "inscription impossible, La capacité maximale de l'événement serait dépassée avec cette inscription.";
        } 
        else {
            // Insertion inscription
            $requete = $pdo->prepare("INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status) VALUES (?, ?, ?, ?, ?)");
            $requete->execute([$id_utilisateur, $id_evenement, $nb_accompagnant, $date_inscription, $status]);
            
            echo "L'inscription a bien été enregistrée.";
        }
    } catch (PDOException $e) {
        echo "Une erreur est survenue : " . $e->getMessage();
    }
}
?>

<h2>Inscrire un participant à un événement</h2>
<form method="POST">
        <label for="id_utilisateur">Utilisateur :</label>
        <select id="id_utilisateur" name="id_utilisateur" required>
            <option value="">-- Sélectionner un utilisateur --</option>
            <?php foreach ($utilisateurs as $utilisateur): ?>
                <option value="<?= htmlspecialchars($utilisateur['id_utilisateur']) ?>">
                    <?= htmlspecialchars($utilisateur['nom'] . ' ' . $utilisateur['prenom'] . ' (' . $utilisateur['email'] . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="id_evenement">Événement :</label>
        <select id="id_evenement" name="id_evenement" required>
            <option value="">-- Sélectionner un événement --</option>
            <?php foreach ($evenements as $evenement): ?>
                <option value="<?= htmlspecialchars($evenement['id_evenement']) ?>">
                    <?= htmlspecialchars($evenement['titre'] . ' (du ' . date('d/m/Y', strtotime($evenement['date_debut'])) . ' au ' . date('d/m/Y', strtotime($evenement['date_fin'])) . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="nb_accompagnant">Nombre d'accompagnants :</label>
        <input type="number" id="nb_accompagnant" name="nb_accompagnant" min="0" value="0" required>

        <button type="submit">Enregistrer l'inscription</button>
</form>