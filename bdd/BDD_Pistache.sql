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
    ('Fantaisie'),
    ('Science-Fiction'),
    ('Historique'),
    ('Post-apocalyptique'),
    ('Horreur'),
    ('Médiéval'),
    ('Pirates'),
    ('Contemporain'),
    ('Mythologie'),
    ('Western');

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

INSERT INTO evenement (titre, description, date_debut, date_fin, capacite_max, duree_type)
VALUES
    ("Atelier '7 Wonders vs Catan'", "Découvre deux piliers du jeu de stratégie moderne ! Apprends à développer ta civilisation, gérer tes ressources et construire ton empire avec 7 Wonders et Les Colons de Catane. Idéal pour les amateurs de stratégie.", "2025-06-15", "2025-06-15", 12, "journée"),
    ("Mission Pandémie", "En équipe, coopérez pour éradiquer les maladies mortelles qui menacent le monde ! Ce scénario immersif vous plonge dans le jeu Pandemic, entre tension et collaboration.", "2025-06-22", "2025-06-22", 8, "demi-journée"),
    ("Soirée Créative Dixit", "Laisse parler ton imagination et ton intuition avec le jeu Dixit ! Une soirée pleine de poésie, d’illustrations magnifiques et de devinettes subtiles. Parfait pour se détendre et rire ensemble.", "2025-06-28", "2025-06-28", 10, "soirée"),
    ("Tournoi Uno + Mille Bornes", "Affronte les autres participants dans un tournoi dynamique où Uno et Mille Bornes seront à l’honneur. De la stratégie, du fun, et surtout beaucoup de cartes à jouer !", "2025-07-06", "2025-07-06", 20, "journée"),
    ("Week-end Mystères & Déductions", "Résous des enquêtes, bluffe tes amis et déduis l’impossible dans un week-end spécial Cluedo, Codenames et Scrabble ! Idéal pour les esprits curieux et logiques.", "2025-07-13", "2025-07-14", 10, "week-end");

-- insertion jeux
INSERT INTO jeux (nom, description_courte, description_longue, annee_sortie, date_ajout, id_genre, id_type, image_path)
VALUES 
    ('Les Colons de Catane', 'Jeu de stratégie et de développement primé', 'Développez des colonies, construisez des routes et des villes, et échangez des ressources dans ce jeu de stratégie et de développement primé.', 1995, CURDATE(), 6, 4, 'assets/images/jeux/ColonsDeCatane.png'),
    ('Dixit', 'Jeu d’imagination et d’intuition', 'Un jeu d’imagination et d’intuition où vous devez deviner ou faire deviner des cartes à partir d’indices subtils et créatifs.', 2008, CURDATE(), 1, 6, 'assets/images/jeux/dixit.png'),
    ('Pandemic', 'Jeu coopératif captivant', 'Travaillez en équipe pour enrayer la propagation de quatre maladies mortelles et sauver l’humanité dans ce jeu coopératif captivant.', 2008, CURDATE(), 8, 3, 'assets/images/jeux/pandemic.png'),
    ('7 Wonders', 'Jeu de cartes et de développement rapide', 'Développez votre civilisation et construisez une merveille qui traversera les âges dans ce jeu de cartes et de développement rapide.', 2010, CURDATE(), 3, 2, 'assets/images/jeux/7wonders.png'),
    ('Uno', 'Le jeu de cartes familial par excellence', 'Le jeu de cartes familial par excellence où il faut être le premier à se débarrasser de toutes ses cartes en les assortissant par couleur ou par numéro.', 1971, CURDATE(), 8, 2, 'assets/images/jeux/uno.png'),
    ('Cluedo', 'Jeu d’enquête classique', 'Menez l’enquête pour découvrir qui a commis le crime, avec quelle arme et dans quelle pièce dans ce jeu d’enquête classique.', 1949, CURDATE(), 8, 9, 'assets/images/jeux/cluedo.png'),
    ('Exploding Kittens', 'Jeu de cartes stratégique rempli d’humour', 'Un jeu de cartes stratégique rempli d’humour où vous devez éviter de piocher les chatons explosifs pour rester dans la partie.', 2015, CURDATE(), 8, 2, 'assets/images/jeux/ExplodingKittens.png'),
    ('Azul', 'Jeu de placement tactique aux règles simples mais profondes', 'Devenez artisan et décorez le palais royal avec de magnifiques carreaux de céramique dans ce jeu de placement tactique aux règles simples mais profondes.', 2017, CURDATE(), 3, 10, 'assets/images/jeux/azul.png'),
    ('Codenames', 'Jeu d’association d’idées simple mais plein de subtilités', 'Un jeu d’association d’idées où vous devez faire deviner des mots à votre équipe en utilisant un seul indice. Simple à comprendre mais plein de subtilités!', 2015, CURDATE(), 8, 9, 'assets/images/jeux/CodeNames.png'),
    ('Takenoko', 'Jeu aux mécaniques innovantes et à l’esthétique charmante', 'Cultivez le bambou, nourrissez le panda et aménagez le jardin de l’empereur du Japon dans ce jeu aux mécaniques innovantes et à l’esthétique charmante.', 2011, CURDATE(), 3, 4, 'assets/images/jeux/takenoko.png'),
    ('Mille Bornes', 'Le célèbre jeu de cartes de course automobile', 'Le célèbre jeu de cartes de course automobile où il faut parcourir 1000 km tout en évitant les embûches tendues par vos adversaires.', 1954, CURDATE(), 8, 2, 'assets/images/jeux/MilleBornes.png'),
    ('Scrabble', 'Le jeu de lettres classique', 'Le jeu de lettres classique où vous devez former des mots entrecroisés sur un plateau pour marquer un maximum de points.', 1948, CURDATE(), 8, 6, 'assets/images/jeux/scrabble.png');
    
-- insertion de l'admin
INSERT INTO utilisateur (nom, prenom, email, mot_de_passe, role)
VALUES ('pist', 'ache', 'pistache280525@gmail.com', 'pisTache2805', 'admin');