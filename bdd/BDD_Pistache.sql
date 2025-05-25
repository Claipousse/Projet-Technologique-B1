DROP DATABASE IF EXISTS bdd_pistache;
CREATE DATABASE bdd_pistache;
USE bdd_pistache;

CREATE TABLE genre(
    id_genre INT NOT NULL AUTO_INCREMENT,
    nom_genre VARCHAR(50) NOT NULL,
    CONSTRAINT PK_GENRE PRIMARY KEY (id_genre)
);

CREATE TABLE type(
    id_type INT NOT NULL AUTO_INCREMENT,
    nom_type VARCHAR(50) NOT NULL,
    CONSTRAINT PK_TYPE PRIMARY KEY (id_type)
);

CREATE TABLE utilisateur(
    id_utilisateur INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(150) NOT NULL,
    role ENUM('admin','participant') NOT NULL, /* ENUM est un type de donnée qui permet de limiter la valeur d'un champ à une liste précise de choix. */
    CONSTRAINT PK_UTI PRIMARY KEY (id_utilisateur)
);

CREATE TABLE jeux(
    id_jeux INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL UNIQUE,
    description_courte TEXT NOT NULL,
    description_longue TEXT NOT NULL,
    annee_sortie YEAR NOT NULL,
    date_ajout DATE NOT NULL,
    id_genre INT NOT NULL,
    id_type INT NOT NULL,
    CONSTRAINT PK_JEUX PRIMARY KEY (id_jeux),
    CONSTRAINT FK_GENRE FOREIGN KEY (id_genre) REFERENCES genre (id_genre),
    CONSTRAINT FK_TYPE FOREIGN KEY (id_type) REFERENCES type (id_type)
);

CREATE TABLE ressource(
    id_ressource INT NOT NULL AUTO_INCREMENT,
    id_jeux INT NOT NULL,
    type_ressource VARCHAR(100) NOT NULL, /* "image" ou "vidéo" etc... */
    url TEXT NOT NULL,
    titre TEXT NOT NULL,
    CONSTRAINT PK_RESS PRIMARY KEY (id_ressource),
    CONSTRAINT FK_JEUX_RESS FOREIGN KEY (id_jeux) REFERENCES jeux (id_jeux)
);

CREATE TABLE evenement(
    id_evenement INT NOT NULL AUTO_INCREMENT,
    titre VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    capacite_max INT NOT NULL,
    duree_type VARCHAR(50) NOT NULL,
    CONSTRAINT PK_EVENT PRIMARY KEY (id_evenement)
);

CREATE TABLE jeux_evenement(
    id_jeux_event INT NOT NULL AUTO_INCREMENT,
    id_jeux INT NOT NULL,
    id_evenement INT NOT NULL,
    CONSTRAINT PK_JEUX_EVENT PRIMARY KEY (id_jeux_event),
    CONSTRAINT FK_JEUX_EVENT FOREIGN KEY (id_jeux) REFERENCES jeux (id_jeux),
    CONSTRAINT FK_EVENT_JEUX FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement)
);

CREATE TABLE inscription(
    id_inscription INT NOT NULL AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_evenement INT NOT NULL,
    nb_accompagnant INT,
    date_inscription DATE NOT NULL,
    status ENUM('en attente', 'validé', 'annulé') NOT NULL,
    CONSTRAINT PK_INSC PRIMARY KEY (id_inscription),
    CONSTRAINT FK_UTI_INSC FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur),
    CONSTRAINT FK_EVENT_INSC FOREIGN KEY (id_evenement) REFERENCES evenement (id_evenement)
);

CREATE TABLE preferences(
    id_inscription INT NOT NULL,
    id_jeux INT NOT NULL,
    CONSTRAINT PK_PREF PRIMARY KEY (id_inscription, id_jeux),
    CONSTRAINT FK_INSC_PREF FOREIGN KEY (id_inscription) REFERENCES inscription (id_inscription),
    CONSTRAINT FK_JEUX_PREF FOREIGN KEY (id_jeux) REFERENCES jeux (id_jeux)
);

alter table jeux add column image_path VARCHAR(255);

-- Insertion des genres
INSERT INTO genre (nom_genre)
VALUES
    ('Stratégie'),
    ('Coopératif'),
    ('Familial'),
    ('Ambiance'),
    ('Enquête'),
    ('Abstrait'),
    ('Éducatif'),
    ('Classique');

-- Insertion des types
INSERT INTO type (nom_type)
VALUES
    ('Jeu de plateau'),
    ('Jeu de cartes'),
    ('Jeu coopératif'),
    ('Jeu de stratégie'),
    ('Jeu de gestion'),
    ('Jeu dambiance'),
    ('Jeu de dés'),
    ('Jeu de rôle'),
    ('Jeu de déduction'),
    ('Jeu de tuiles');

-- Insertion des jeux
INSERT INTO jeux (nom, description_courte, description_longue, annee_sortie, date_ajout, id_genre, id_type, image_path)
VALUES 
    ("Les Colons de Catane", "Construisez colonies et routes sur une île en échangeant ressources pour développer votre civilisation.", "Chaque joueur incarne un colon sur l'île de Catane composée de tuiles hexagonales produisant différentes ressources (bois, argile, minerai, blé, laine). Lancez les dés pour déterminer les ressources produites, construisez des routes (1 bois + 1 argile), des colonies (1 bois + 1 argile + 1 blé + 1 laine) et des villes (3 minerais + 2 blés). Les colonies valent 1 point, les villes 2 points. Échangez avec les autres joueurs et achetez des cartes développement. Premier à 10 points de victoire remporte la partie.", 1995, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Stratégie"), 4, "assets/images/jeux/ColonsDeCatane.png"),
    ("Dixit", "Faites deviner vos cartes avec des indices créatifs grâce aux illustrations oniriques.", "Chaque joueur a 6 cartes illustrées. Le conteur choisit une carte et donne un indice oral (mot, phrase, chanson). Les autres joueurs choisissent dans leur main la carte qui correspond le mieux à cet indice. Toutes les cartes sont mélangées et exposées. Chaque joueur vote pour la carte du conteur. Le conteur marque des points seulement si certains (mais pas tous) trouvent sa carte. Les joueurs marquent aussi des points pour chaque vote reçu. Première à 30 points ou fin de pioche.", 2008, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Ambiance"), 6, "assets/images/jeux/dixit.png"),
    ("Pandemic", "Équipe de spécialistes contre 4 maladies mortelles qui menacent l'humanité entière.", "Formez une équipe de spécialistes (médecin, scientifique, etc.) pour combattre 4 maladies sur la planète. À chaque tour : 4 actions (se déplacer, traiter des maladies, construire des centres, échanger des cartes), puis piochez 2 cartes et révélez des épidémies. Pour gagner, découvrez les 4 remèdes en collectant 5 cartes de même couleur dans un centre de recherche. Vous perdez si 8 épidémies éclatent, plus de 24 cubes d'une maladie sont posés, ou si la pioche est épuisée.", 2008, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Coopératif"), 3, "assets/images/jeux/pandemic.png"),
    ("7 Wonders", "Dirigez une civilisation antique et développez votre cité par le commerce et la science.", "Jeu de draft où chaque joueur dirige une merveille antique. Recevez 7 cartes, choisissez-en une simultanément, révélez et appliquez, puis passez le reste à votre voisin. Les cartes représentent : ressources, bâtiments civils, structures scientifiques, commerciales et militaires. Payez le coût en ressources (produites ou achetées aux voisins). Conflits militaires à la fin de chaque âge. Construisez votre merveille pour des effets puissants. Après 3 âges, le joueur avec le plus de points de victoire gagne.", 2010, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Stratégie"), 2, "assets/images/jeux/7wonders.png"),
    ("Uno", "Videz votre main en premier en jouant couleurs et chiffres, attention aux cartes spéciales !", "Jeu de 108 cartes avec 4 couleurs (numéros 0-9) plus cartes spéciales : +2, Inversion, Passer le tour, Joker et +4. Chaque joueur commence avec 7 cartes. Posez une carte de même couleur, chiffre ou symbole que celle sur la défausse. Si impossible, piochez. Quand il ne vous reste qu'une carte, criez 'UNO !' sinon piochez 2 cartes en pénalité. Premier à vider sa main gagne la manche et marque des points selon les cartes adverses restantes.", 1971, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Familial"), 2, "assets/images/jeux/uno.png"),
    ("Cluedo", "Enquête policière au manoir : découvrez qui a tué, avec quoi et où dans cette affaire.", "Un meurtre s'est produit : une carte Suspect, Arme et Lieu sont secrètement mises de côté. Les autres cartes sont distribuées aux joueurs. Déplacez-vous dans le manoir en lançant les dés. Dans chaque pièce, formulez des hypothèses (suspect + arme + lieu). Les autres joueurs montrent secrètement une carte qu'ils possèdent parmi celles citées. Éliminez progressivement les possibilités. Quand vous pensez connaître la solution, faites une accusation. Si c'est correct, vous gagnez. Si c'est faux, vous êtes éliminé.", 1949, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Enquête"), 9, "assets/images/jeux/cluedo.png"),
    ("Exploding Kittens", "Évitez les chatons explosifs et sabotez vos adversaires dans ce jeu déjanté et hilarant.", "Jeu d'élimination avec des cartes Chatons Explosifs (pièges) et cartes d'action. Chaque joueur commence avec des cartes + 1 Désamorçage. À votre tour, jouez autant de cartes d'action que voulu (Attaque, Passer, Voir l'avenir, Mélanger, Vol), puis piochez obligatoirement une carte. Si vous piochez un Chaton Explosif, vous explosez et êtes éliminé... sauf si vous utilisez un Désamorçage ! Replacez alors secrètement le Chaton n'importe où dans la pioche. Dernier survivant gagne.", 2015, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Ambiance"), 2, "assets/images/jeux/ExplodingKittens.png"),
    ("Azul", "Artisan carreleur : collectez tuiles colorées pour décorer les murs du palais royal.", "Collectez des tuiles colorées depuis des présentoirs circulaires ou le centre de table. Prenez toutes les tuiles d'une même couleur et placez-les sur une ligne de motif de votre plateau personnel (ligne 1 = 1 tuile, ligne 2 = 2 tuiles, etc.). Quand une ligne est complète, déplacez 1 tuile vers le mur décoratif et défaussez les autres. Marquez des points selon les tuiles adjacentes sur le mur. Bonus pour lignes, colonnes et couleurs complètes. Fin quand un joueur complète une ligne horizontale.", 2017, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Abstrait"), 10, "assets/images/jeux/azul.png"),
    ("Codenames", "Deux équipes s'affrontent pour retrouver leurs agents secrets grâce à des indices subtils.", "25 cartes-mots sont étalées en grille 5x5. Deux équipes (rouge et bleue) ont chacune un chef d'équipe qui connaît l'identité secrète des mots (agents rouges, bleus, neutres, assassin). À tour de rôle, chaque chef donne un indice (un mot + un chiffre) pour faire deviner les mots de son équipe. Ex : 'Animal 2' pour faire deviner 'Chat' et 'Chien'. L'équipe désigne les mots un par un jusqu'à se tromper ou atteindre le nombre indiqué. Si elle touche l'assassin, elle perd immédiatement. Première équipe à retrouver tous ses agents gagne.", 2015, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Enquête"), 9, "assets/images/jeux/CodeNames.png"),
    ("Takenoko", "Cultivez bambou et nourrissez le panda dans le jardin japonais de l'empereur.", "Aménagez un jardin en posant des tuiles terrain hexagonales de 3 couleurs (vert, jaune, rose). Le panda se déplace et mange du bambou, le jardinier fait pousser le bambou. À votre tour, lancez le dé météo puis choisissez 2 actions parmi : piocher une tuile et la poser, déplacer le panda et manger du bambou, déplacer le jardinier et faire pousser du bambou, piocher une carte objectif, ou prendre un aménagement. Accomplissez vos objectifs : motifs de tuiles, configurations de bambou ou quantités mangées par le panda. Premier à compléter un certain nombre d'objectifs gagne.", 2011, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Familial"), 4, "assets/images/jeux/takenoko.png"),
    ("Mille Bornes", "Course automobile : parcourez 1000 km en évitant les embûches de vos adversaires.", "Jeu de course où chaque joueur doit atteindre exactement 1000 km. Jouez des cartes Étape (25, 50, 75, 100, 200 km) pour avancer. Vos adversaires peuvent vous attaquer avec des cartes Attaque (Accident, Panne d'essence, Crevaison, Limitation de vitesse, Feu rouge). Défendez-vous avec des cartes Parade correspondantes ou des cartes Botte (As du volant, Citerne, Increvable, Prioritaire) qui immunisent définitivement. Certaines cartes donnent des points bonus. Premier à 1000 km exactement gagne la manche. Plusieurs manches jusqu'à 5000 points au total.", 1954, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Classique"), 2, "assets/images/jeux/MilleBornes.png"),
    ("Scrabble", "Formez des mots entrecroisés avec vos lettres pour marquer un maximum de points.", "Formez des mots entrecroisés sur un plateau 15x15 avec vos 7 lettres piochées. Chaque lettre a une valeur en points. Posez au moins un mot par tour en vous appuyant sur les mots existants. Premier mot doit passer par la case centrale étoile. Exploitez les cases spéciales : lettre compte double/triple, mot compte double/triple. Si vous utilisez vos 7 lettres d'un coup, bonus de 50 points. Piochez de nouvelles lettres pour maintenir 7 en main. Quand un joueur n'a plus de lettres ou que personne ne peut jouer, la partie se termine. Joueur avec le plus de points gagne.", 1948, CURDATE(), (SELECT id_genre FROM genre WHERE nom_genre = "Éducatif"), 6, "assets/images/jeux/scrabble.png");

-- Insertion des événements
INSERT INTO evenement (titre, description, date_debut, date_fin, capacite_max, duree_type)
VALUES
    ("Atelier '7 Wonders vs Catan'", "Découvre deux piliers du jeu de stratégie moderne ! Apprends à développer ta civilisation, gérer tes ressources et construire ton empire avec 7 Wonders et Les Colons de Catane. Idéal pour les amateurs de stratégie.", "2025-06-15", "2025-06-15", 12, "journée"),
    ("Mission Pandémie", "En équipe, coopérez pour éradiquer les maladies mortelles qui menacent le monde ! Ce scénario immersif vous plonge dans le jeu Pandemic, entre tension et collaboration.", "2025-06-22", "2025-06-22", 8, "demi-journée"),
    ("Soirée Créative Dixit", "Laisse parler ton imagination et ton intuition avec le jeu Dixit ! Une soirée pleine de poésie, d'illustrations magnifiques et de devinettes subtiles. Parfait pour se détendre et rire ensemble.", "2025-06-28", "2025-06-28", 10, "demi-journée"),
    ("Tournoi Uno + Mille Bornes", "Affronte les autres participants dans un tournoi dynamique où Uno et Mille Bornes seront à l'honneur. De la stratégie, du fun, et surtout beaucoup de cartes à jouer !", "2025-07-06", "2025-07-06", 20, "journée"),
    ("Week-end Mystères & Déductions", "Résous des enquêtes, bluffe tes amis et déduis l'impossible dans un week-end spécial Cluedo, Codenames et Scrabble ! Idéal pour les esprits curieux et logiques.", "2025-07-13", "2025-07-14", 10, "weekend");

-- Insertion des associations jeux-événements
INSERT INTO jeux_evenement (id_jeux, id_evenement)
SELECT j.id_jeux, e.id_evenement 
FROM jeux j, evenement e 
WHERE 
    -- Atelier '7 Wonders vs Catan'
    (j.nom IN ("7 Wonders", "Les Colons de Catane") AND e.titre = "Atelier '7 Wonders vs Catan'") OR
    -- Mission Pandémie  
    (j.nom = "Pandemic" AND e.titre = "Mission Pandémie") OR
    -- Soirée Créative Dixit
    (j.nom = "Dixit" AND e.titre = "Soirée Créative Dixit") OR
    -- Tournoi Uno + Mille Bornes
    (j.nom IN ("Uno", "Mille Bornes") AND e.titre = "Tournoi Uno + Mille Bornes") OR
    -- Week-end Mystères & Déductions
    (j.nom IN ("Cluedo", "Codenames", "Scrabble") AND e.titre = "Week-end Mystères & Déductions");

-- Insertion d'un compte Admin
INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role)
VALUES ('Admin', 'Admin', 'admin@pistache.com', '$2y$10$s8Esr76stvOWzBFJKD49IO07S6Pk8.1yk9AcdK7LC0oz11sreM0bG', 'admin');