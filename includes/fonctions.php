<?php

/*Ce fichier sert à répertorier de nombreuses fonctions que nous appellerons dans les autres fichiers .php, comme
la plupart des fonctions seront utilisés plusieurs fois, s'il y a un problème avec l'une d'elle, il suffira de modifier
ce fichier pour patcher le problème dans tous les autres fichiers, au lieu de modifier chaques fichiers où la fonction
est présente.*/

require_once __DIR__ . "/../config/config.php";

/* Genère une alerte HTML à afficher, $message: message à afficher, $type: success, danger, warning */
function alerte($message, $type = 'info') {
    return '<div class="alert alert-' . $type . '" role="alert">
    ' . $message . '
    <button type="button" class="btn-close" data-dismiss="alert" aria-label="Fermer"></button>
    </div>';
}

// Vérifie si une chaîne est vide ou nulle (true si vide, sinon false)
function estVide($chaine) {
    return (!isset($chaine) || empty(trim($chaine)));
}

//Obtenir l'URL actuelle
function urlActuelle() {
    /*On vérifie d'abord le protocole utilisé avec une condition ternaire (https ou http),
    puis on concatène la string avec le reste de l'url */
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

//Redirige vers une url avec un message
function redirigerAvecMessage($url, $message, $type = 'success') {
    // Déterminer le séparateur (? ou &) selon si l'URL a déjà des paramètres
    $separator = strpos($url, '?') !== false ? '&' : '?';

    // Ajouter les paramètres message et type à l'URL
    $url .= $separator . 'message=' . urlencode($message) . '&type=' . urlencode($type);

    rediriger($url); //rediriger() est une fonction de config.php
}

//Savoir si un jeu existe dans la BDD
function jeuExiste($nom, $id = null) //id permet d'exclure un jeu de la recherche
{
    $conn = connexionBDD(); //connexionBDD() vient de config.php
    $sql = "SELECT id_jeux FROM jeux WHERE nom = :nom"; //Cherche dans la table jeu si le nom d'un jeu à la valeur de $nom
    $params = [':nom' => $nom]; //Crée un tableau où :nom prend la valeur du paramètre $nom (jeu que l'on cherche)

    if ($id !== null) { //Si id fourni, on ajoute une instruction pour dégager ce jeu de la recherche
        $sql .= " AND id_jeux != :id";
        $params[':id'] = $id;
    }

    $stmt = $conn->prepare($sql); //prépare la requête
    $stmt->execute($params); //éxécute la requête avec les paramètres définis

    return $stmt->rowCount() > 0; //si au moins 1 jeu à été trouvé true, sinon false
}

//Savoir si un événement existe à une date donnée
function evenementExiste($date_debut, $date_fin, $id = null) /*id = paramètre optionnel permettant exclure un événement de la recherche */{
    $conn = connexionBDD();
    $sql = "SELECT id_evenement FROM evenement WHERE 
            (date_debut BETWEEN :date_debut AND :date_fin) OR --La date de début d'un événement existant tombe entre les dates fournies
            (date_fin BETWEEN :date_debut AND :date_fin) OR --La date de fin d'un événement existant tombe entre les dates fournies
            (:date_debut BETWEEN date_debut AND date_fin) OR --La date de début fournie tombe pendant un événement existant
            (:date_fin BETWEEN date_debut AND date_fin) -- La date de fin fournie tombe pendant un événement existant";

    /*Si id fourni, requête ajoute une condition pour exclure l'événement avec cet ID
    utile lors de la mise à jour d'un événement existant pour ne pas le considérer comme en conflit avec lui-même*/
    if ($id !== null) {
        $sql .= " AND id_evenement != :id";
        $params[':id'] = $id;
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount() > 0; //Si au moins 1 événement à été trouvé true, sinon false
}

// Fonction pour formater les dates d'événements en français
if (!function_exists('formaterDateEvenement')) {
    function formaterDateEvenement($date_debut, $date_fin, $avec_le = false) {
        $mois = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
        ];

        $debut = new DateTime($date_debut);
        $fin = new DateTime($date_fin);

        $prefixe = $avec_le ? "Le " : "";

        if ($debut->format('Y-m-d') === $fin->format('Y-m-d')) {
            // Même jour
            return $prefixe . $debut->format('j') . " " . $mois[(int)$debut->format('n')];
        } else {
            // Jours différents
            $jour_debut = $debut->format('j');
            $jour_fin = $fin->format('j');
            $mois_fin = $mois[(int)$fin->format('n')];

            if ($debut->format('n') === $fin->format('n')) {
                // Même mois
                return $prefixe . $jour_debut . " & " . $jour_fin . " " . $mois_fin;
            } else {
                // Mois différents - on prend le mois de la fin
                return $prefixe . $jour_debut . " & " . $jour_fin . " " . $mois_fin;
            }
        }
    }
}


  //Récupère tous les événements disponibles
function getEvenements() {
    $conn = connexionBDD(); 
    
    try {
        $stmt = $conn->query("SELECT * FROM evenement ORDER BY date_debut ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la récupération des événements : " . $e->getMessage());
    }
}

 // Récupère tous les jeux disponibles
function getJeux() {
    $conn = connexionBDD(); 
    
    try {
        $stmt = $conn->query("SELECT * FROM jeux ORDER BY nom ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la récupération des jeux : " . $e->getMessage());
    }
}

// Calcule le nombre de places disponibles pour un événement
function getPlacesDisponibles($id_evenement, $capacite_max) {
    $conn = connexionBDD(); 
    
    try {
        $stmt = $conn->prepare("
            SELECT COALESCE(SUM(1 + COALESCE(nb_accompagnant, 0)), 0) as places_prises
            FROM inscription 
            WHERE id_evenement = ? 
            AND status IN ('en attente', 'validé')
        ");
        $stmt->execute([$id_evenement]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $places_prises = $result['places_prises'] ?? 0;
        return max(0, $capacite_max - $places_prises);
        
    } catch (PDOException $e) {
        throw new Exception("Erreur lors du calcul des places disponibles : " . $e->getMessage());
    }
}


// Vérifie si un utilisateur existe déjà
function utilisateurExiste($email) {
    $conn = connexionBDD(); 
    
    try {
        $stmt = $conn->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la vérification de l'utilisateur : " . $e->getMessage());
    }
}


 //Crée un nouvel utilisateur
function creerUtilisateur($nom, $prenom, $email) {
    $conn = connexionBDD(); 
    
    try {
        // Générer un mot de passe temporaire
        $mot_de_passe_temp = bin2hex(random_bytes(8));
        $mot_de_passe_hash = password_hash($mot_de_passe_temp, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role) 
            VALUES (?, ?, ?, ?, 'participant')
        ");
        $stmt->execute([$nom, $prenom, $email, $mot_de_passe_hash]);
        
        return [
            'id' => $conn->lastInsertId(),
            'mot_de_passe_temp' => $mot_de_passe_temp
        ];
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la création de l'utilisateur : " . $e->getMessage());
    }
}

 // Vérifie si l'utilisateur est déjà inscrit à cet événement
function utilisateurDejaInscrit($id_utilisateur, $id_evenement) {
    $conn = connexionBDD(); 
    
    try {
        $stmt = $conn->prepare("
            SELECT id_inscription 
            FROM inscription 
            WHERE id_utilisateur = ? AND id_evenement = ? 
            AND status IN ('en attente', 'validé')
        ");
        $stmt->execute([$id_utilisateur, $id_evenement]);
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la vérification d'inscription : " . $e->getMessage());
    }
}


 // Vérifie si les jeux sélectionnés sont valides
function verifierJeuxValides($jeux_selectionnes) {
    $conn = connexionBDD(); 
    
    if (empty($jeux_selectionnes)) {
        return true; // Pas de jeux sélectionnés, c'est valide
    }
    
    try {
        $placeholders = str_repeat('?,', count($jeux_selectionnes) - 1) . '?';
        $stmt = $conn->prepare("SELECT id_jeux FROM jeux WHERE id_jeux IN ($placeholders)");
        $stmt->execute($jeux_selectionnes);
        $jeux_existants = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        return count($jeux_existants) === count($jeux_selectionnes);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la vérification des jeux : " . $e->getMessage());
    }
}

//Fonction d'inscription
function inscrireUtilisateur($nom, $prenom, $email, $nb_accompagnant, $id_evenement, $jeux_selectionnes = []) {
    $conn = connexionBDD(); 
    
    try {
        $conn->beginTransaction();
        
        // Vérifier les places disponibles
        $evenement_info = $conn->prepare("SELECT capacite_max FROM evenement WHERE id_evenement = ?");
        $evenement_info->execute([$id_evenement]);
        $evenement = $evenement_info->fetch(PDO::FETCH_ASSOC);
        
        if (!$evenement) {
            throw new Exception("Événement non trouvé");
        }
        
        $places_disponibles = getPlacesDisponibles($id_evenement, $evenement['capacite_max']);
        $places_demandees = 1 + $nb_accompagnant; 
        
        if ($places_disponibles < $places_demandees) {
            throw new Exception("Il n'y a pas assez de places disponibles. Places disponibles: $places_disponibles, places demandées: $places_demandees");
        }
        
        $utilisateur = utilisateurExiste($email);
        
        if (!$utilisateur) {
            $nouveau_utilisateur = creerUtilisateur($nom, $prenom, $email);
            $id_utilisateur = $nouveau_utilisateur['id'];
            $mot_de_passe_temp = $nouveau_utilisateur['mot_de_passe_temp'];
            $nouveau_compte = true;
        } else {
            $id_utilisateur = $utilisateur['id_utilisateur'];
            $nouveau_compte = false;
            
            // Vérifier si déjà inscrit à cet événement
            if (utilisateurDejaInscrit($id_utilisateur, $id_evenement)) {
                throw new Exception("Vous êtes déjà inscrit à cet événement");
            }
        }
        
        if (!empty($jeux_selectionnes) && !verifierJeuxValides($jeux_selectionnes)) {
            throw new Exception("Un ou plusieurs jeux sélectionnés ne sont pas valides");
        }
        
        // Créer l'inscription
        $stmt = $conn->prepare("
            INSERT INTO inscription (id_utilisateur, id_evenement, nb_accompagnant, date_inscription, status) 
            VALUES (?, ?, ?, CURDATE(), 'en attente')
        ");
        $stmt->execute([$id_utilisateur, $id_evenement, $nb_accompagnant]);
        $id_inscription = $conn->lastInsertId();
        
        // Ajouter les préférences de jeux
        if (!empty($jeux_selectionnes)) {
            $stmt_pref = $conn->prepare("INSERT INTO preferences (id_inscription, id_jeux) VALUES (?, ?)");
            foreach ($jeux_selectionnes as $id_jeu) {
                $stmt_pref->execute([$id_inscription, $id_jeu]);
            }
        }
        
        $conn->commit();
        
        $message = "Inscription réussie !";
        if ($nouveau_compte) {
            $message .= " Un compte a été créé pour vous. Votre mot de passe temporaire est : <strong>$mot_de_passe_temp</strong><br>";
            $message .= "Veuillez le noter et le changer lors de votre première connexion.";
        }
        
        return [
            'success' => true,
            'message' => $message,
            'id_inscription' => $id_inscription
        ];
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollBack();
        
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Récupère les inscriptions d'un utilisateur (version corrigée)
function getInscriptionsUtilisateur($id_utilisateur) {
    $conn = connexionBDD();

    try {
        $stmt = $conn->prepare("
            SELECT i.*, e.titre, e.description, e.date_debut, e.date_fin, e.duree_type
            FROM inscription i
            JOIN evenement e ON i.id_evenement = e.id_evenement
            WHERE i.id_utilisateur = ?
            AND i.status IN ('en attente', 'validé')
            AND e.date_fin >= CURDATE()
            ORDER BY e.date_debut ASC, i.date_inscription DESC
        ");
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la récupération des inscriptions : " . $e->getMessage());
    }
}

 // Récupère les préférences de jeux pour une inscription
function getPreferencesInscription($id_inscription) {
    $conn = connexionBDD(); 
    
    try {
        $stmt = $conn->prepare("
            SELECT j.id_jeux, j.nom, j.description_courte
            FROM preferences p
            JOIN jeux j ON p.id_jeux = j.id_jeux
            WHERE p.id_inscription = ?
        ");
        $stmt->execute([$id_inscription]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la récupération des préférences : " . $e->getMessage());
    }
}

// Fonction pour désinscrire un utilisateur d'un événement
function desinscrireUtilisateur($id_inscription, $id_utilisateur) {
    $conn = connexionBDD();

    try {
        $conn->beginTransaction();

        // Vérifier que l'inscription appartient bien à l'utilisateur et qu'elle existe
        $stmt = $conn->prepare("
            SELECT i.*, e.date_debut, e.titre 
            FROM inscription i 
            JOIN evenement e ON i.id_evenement = e.id_evenement 
            WHERE i.id_inscription = ? AND i.id_utilisateur = ? 
            AND i.status IN ('en attente', 'validé')
        ");
        $stmt->execute([$id_inscription, $id_utilisateur]);
        $inscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$inscription) {
            throw new Exception("Inscription non trouvée ou déjà annulée");
        }

        // Vérifier que l'événement n'est pas déjà passé
        if ($inscription['date_debut'] < date('Y-m-d')) {
            throw new Exception("Impossible de se désinscrire d'un événement passé");
        }

        // Supprimer les préférences de jeux liées à cette inscription
        $stmt_pref = $conn->prepare("DELETE FROM preferences WHERE id_inscription = ?");
        $stmt_pref->execute([$id_inscription]);

        // Supprimer l'inscription au lieu de la marquer comme annulée
        $stmt_del = $conn->prepare("DELETE FROM inscription WHERE id_inscription = ?");
        $stmt_del->execute([$id_inscription]);

        $conn->commit();

        return [
            'success' => true,
            'message' => "Vous avez été désinscrit avec succès de l'événement \"" . htmlspecialchars($inscription['titre'])
        ];

    } catch (Exception $e) {
        $conn->rollBack();

        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}