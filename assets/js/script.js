// script.js - Code JavaScript centralisé pour Pistache
// Ce fichier regroupe tout le code JavaScript utilisé dans les différentes pages client

// DOMContentLoaded -> Attend que tout le HTML soit chargé avant d'éxécuter le JS
// Evite erreurs si scripts veut accéder à des éléments pas encore crées
document.addEventListener('DOMContentLoaded', function() {

    // Menu Toggle : Sur mobile, menu caché pour économiser de la place, on peut cliquer sur '☰', pour faire apparaitre le menu
    const menuToggle = document.getElementById("menuToggle");
    const mainMenu = document.getElementById("mainMenu");

    if (menuToggle && mainMenu) {
        menuToggle.addEventListener("click", function () {
            mainMenu.classList.toggle("active");
        });
    }

    // Smooth Scroll (Permet de faire fonctionner la flèche ou le bouton "Découvrir notre site" sur index.php client)
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute("href"));
            if (target) {
                target.scrollIntoView({
                    behavior: "smooth", // Déplacement fluide et pas instantanée
                });
            }

            // Sur mobile, si on fait un smooth scroll, on ferme le menu mobile si ouvert
            const menu = document.getElementById("mainMenu");
            if (menu?.classList.contains("active")) {
                menu.classList.remove("active");
            }
        });
    });

    // Menu déroulant (Dropdown), en haut à droite du menu & contient "Administration" & "Se déconnecter"
    const dropdown = document.querySelector('.dropdown');
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownContent = document.querySelector('.dropdown-content');

    if (dropdown && dropdownToggle && dropdownContent) {
        // Fonction pour afficher le menu
        function showDropdown() {
            dropdownContent.classList.add('show');
            dropdownToggle.setAttribute('aria-expanded', 'true');
        }

        // Fonction pour cacher le menu
        function hideDropdown() {
            dropdownContent.classList.remove('show');
            dropdownToggle.setAttribute('aria-expanded', 'false');
        }

        // Toggle au clic sur le bouton
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (dropdownContent.classList.contains('show')) {
                hideDropdown();
            } else {
                // Fermer tous les autres dropdowns ouverts (au cas où il y en aurait plusieurs)
                document.querySelectorAll('.dropdown-content.show').forEach(function(openDropdown) {
                    if (openDropdown !== dropdownContent) {
                        openDropdown.classList.remove('show');
                        const toggle = openDropdown.previousElementSibling;
                        if (toggle) toggle.setAttribute('aria-expanded', 'false');
                    }
                });
                showDropdown();
            }
        });

        // Clic en dehors pour fermer
        document.addEventListener('click', function(event) {
            if (!dropdown.contains(event.target)) {
                hideDropdown();
            }
        });

        // Fermer avec la touche Escape
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                hideDropdown();
            }
        });

        // Affiche si souris dessus (optionnel : comportement hover comme Bootstrap)
        dropdown.addEventListener('mouseenter', function() {
            showDropdown();
        });

        // Cache si souris se casse
        dropdown.addEventListener('mouseleave', function() {
            hideDropdown();
        });

        // Initialiser l'état
        dropdownToggle.setAttribute('aria-expanded', 'false');
    }

});