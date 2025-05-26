# Pistache - Boutique de Jeux de Société

## Maquettes Figma

Les maquettes du projet sont disponibles sur Figma :

- [Page d'accueil](https://www.figma.com/proto/rakd7GQ6bXkukzcapskQbZ/Projet-B1?node-id=0-1&t=MyTr0CSZAhsh1DDl-1)
- [Catalogue](https://www.figma.com/proto/rakd7GQ6bXkukzcapskQbZ/Projet-B1?node-id=1-2&t=MyTr0CSZAhsh1DDl-1)
- [Événements](https://www.figma.com/proto/rakd7GQ6bXkukzcapskQbZ/Projet-B1?node-id=127-363&t=MyTr0CSZAhsh1DDl-1)
- [Se Connecter](https://www.figma.com/proto/rakd7GQ6bXkukzcapskQbZ/Projet-B1?node-id=127-706&t=MyTr0CSZAhsh1DDl-1)
- [Assets](https://www.figma.com/proto/rakd7GQ6bXkukzcapskQbZ/Projet-B1?node-id=127-728&t=MyTr0CSZAhsh1DDl-1)

## Fonctionnalités

### Côté public
- **Page d'accueil** : Présentation de la boutique avec les derniers jeux ajoutés et prochains événements
- **Catalogue de jeux** : Navigation avec filtres par genre, recherche textuelle et pagination
- **Détail des jeux** : Informations complètes, règles, documents PDF et vidéos YouTube
- **Événements** : Liste des événements avec filtres et système d'inscription
- **Authentification** : Création de compte et connexion utilisateur

### Côté utilisateur connecté
- **Inscription aux événements** : Possibilité de s'inscrire avec accompagnants et préférences de jeux
- **Gestion des inscriptions** : Consultation et désinscription depuis l'espace personnel

### Administration
- **Gestion des jeux** : CRUD complet avec upload d'images, PDF et vidéos
- **Gestion des événements** : Création, modification et suppression d'événements
- **Gestion des inscriptions** : Validation/refus des inscriptions en attente
- **Tableau de bord** : Statistiques et vue d'ensemble de la plateforme

## Technologies utilisées

- **Backend** : PHP 8+ avec PDO
- **Base de données** : MySQL
- **Frontend** : HTML5, CSS3, JavaScript ES6+
- **Frameworks CSS** : Bootstrap 5 (administration)
- **Icons** : Font Awesome 6
- **Fonts** : Google Fonts (Playfair Display, Lora)

## Structure du projet

```
pistache/
├── admin/                    # Interface d'administration
│   ├── evenements/          # Gestion des événements
│   ├── inscriptions/        # Gestion des inscriptions
│   ├── jeux/               # Gestion des jeux
│   └── includes/           # En-têtes et pieds de page admin
├── assets/                  # Ressources statiques
│   ├── css/                # Styles CSS
│   ├── js/                 # Scripts JavaScript
│   ├── images/             # Images du site et jeux
│   └── pdf/                # Documents PDF
├── auth/                   # Authentification
├── bdd/                    # Scripts SQL
├── config/                 # Configuration et connexion BDD
├── includes/               # Composants réutilisables
├── user/                   # Espace utilisateur
└── *.php                   # Pages principales
```

## Installation

### Prérequis
- Serveur web (Apache/Nginx)
- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Extension PDO activée

### Configuration
1. Cloner le repository
2. Importer le fichier `bdd/BDD_Pistache.sql` dans MySQL
3. Modifier les paramètres de connexion dans `config/config.php`
4. Configurer le serveur web pour pointer vers le dossier racine

### Données de test
- **Compte administrateur** : admin@pistache.com / root
- La base de données inclut des jeux et événements de démonstration

## Architecture

Le projet suit une architecture MVC simplifiée :
- **Modèle** : Fonctions de base de données dans `config/` et `includes/`
- **Vue** : Templates PHP avec séparation header/footer
- **Contrôleur** : Logique métier intégrée dans les pages PHP

L'interface d'administration est complètement séparée de l'interface publique avec ses propres styles et scripts.

## Responsive Design

Le site est entièrement responsive avec :
- Menu mobile avec toggle hamburger
- Grilles adaptatives pour les cartes
- Typography et espacement optimisés par device
- Interface tactile friendly