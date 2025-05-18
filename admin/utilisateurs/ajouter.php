<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $email = $_POST['email'];
    $mot_de_passe = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);
    $role = 'participant';

try {
    $pdo = connexionBDD();

    $requete = $pdo->prepare("INSERT INTO utilisateur  (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)");
    $requete->execute([$nom, $prenom, $email, $mot_de_passe, $role]);

    echo "Le participant a bien été ajouté.";
}
catch (PDOException $e) {
    if($e->getCode() == 23000){
        echo "cette adresse email est déja utilisée";}
    else {echo "une erreur est survenue : " . $e->getMessage();}
}
}

?>

<h2>Ajouter un participant</h2>
<form method="POST">
    <label for="nom">Nom :</label>
    <input type="text" id="nom" name="nom" required>

    <label for="prenom">Prenom :</label>
    <input type="text" id="prenom" name="prenom" required>

    <label for="email">Email :</label>
    <input type="email" id="email" name="email" required>

    <label for="mot_de_passe">Mot de passe :</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required>

    <button type="submit">Ajoutez l'utilisateur</button>
</form>
