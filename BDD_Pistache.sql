create database bdd_pistache;
use bdd_pistache;

create table utilisateur(
    id_utilisateur int not null auto_increment,
    nom varchar(50) not null,
    prenom varchar(50) not null,
    email varchar(100) not null unique,
    mot_de_passe varchar(150) not null,
    role ENUM('admin','participant') not null, --ENUM est un type de donnée qui permet de limiter la valeur d’un champ à une liste précise de choix.
    constraint PK_UTI primary key (id_utilisateur)
);

create table jeux(
    id_jeux int not null auto_increment,
    nom varchar(50) not null unique,
    description_courte text not null,
    description_longue text not null,
    annee_sortie year not null,
    date_ajout date not null,
    id_genre int not null,
    id_type int not null,
    constraint PK_JEUX primary key (id_jeux),
    constraint FK_GENRE foreign key (id_genre) references genre (id_genre),
    constraint FK_TYPE foreign key (id_type) references type (id_type)
);

create table genre(
    id_genre int not null auto_increment,
    nom_genre varchar(50) not null,
    constraint PK_GENRE primary key (id_genre)
);

create table type(
    id_type int not null auto_increment,
    nom_type varchar(50) not null,
    constraint PK_TYPE primary key (id_type)
);

create table ressource(
    id_ressource int not null auto_increment,
    id_jeux int not null,
    type_ressource varchar(100) not null, --"image" ou "vidéo" etc...
    url text not null,
    titre text not null,
    constraint PK_RESS primary key (id_ressource),
    constraint FK_JEUX foreign key (id_jeux) references jeux (id_jeux)
);

create table evenement(
    id_evenement int not null auto_increment,
    titre varchar(100) not null,
    description text not null, 
    date_debut date not null,
    date_fin date not null,
    capacite_max int not null,
    duree_type varchar(50)not null,
    constraint PK_event primary key (id_evenement)
);

create table jeux_evenement(
    id_jeux_event int not null auto_increment,
    id_jeux int not null,
    id_evenement int not null,
    constraint PK_JEUX_EVENT primary key (id_jeux_event),
    constraint FK_JEUX foreign key (id_jeux) references jeux (id_jeux),
    constraint FK_EVENT foreign key (id_evenement) references evenement (id_evenement)
);

create table inscription(
    id_inscription int not null auto_increment,
    id_utilisateur int not null,
    id_evenement int not null,
    nb_accompagnant int, 
    date_inscription date not null,
    status ENUM('en attente', 'validé', 'annulé'),
    constraint PK_INSC primary key (id_inscription),
    constraint FK_UTI foreign key (id_utilisateur) references utilisateur (id_utilisateur),
    constraint FK_EVENT foreign key (id_evenement) references evenement (id_evenement)
);

create table preferences(
    id_preference int not null auto_increment,
    id_inscription int not null,
    id_jeux int not null,
    constraint PK_PREF primary key (id_preference),
    constraint FK_INSC foreign key (id_inscription) references inscription (id_inscription),
    constraint FK_JEUX foreign key (id_jeux) references jeux (id_jeux)
);