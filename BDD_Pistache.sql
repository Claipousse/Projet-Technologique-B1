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