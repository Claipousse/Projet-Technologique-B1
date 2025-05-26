/* ===================================
   JAVASCRIPT COMMUN À TOUTES LES PAGES
   =================================== */

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

/* ===================================
   JAVASCRIPT SPÉCIFIQUE À EVENEMENTS.PHP
   =================================== */

// Variables globales pour la gestion du dropdown des jeux
let jeuxDropdownOpen = false;

// Fonction pour toggler le dropdown des jeux dans les filtres
function toggleJeuxDropdown(event) {
    event.preventDefault();
    event.stopPropagation();

    const dropdown = document.getElementById('jeuxDropdown');
    const icon = event.target.closest('.jeux-dropdown-btn').querySelector('i');

    jeuxDropdownOpen = !jeuxDropdownOpen;

    if (jeuxDropdownOpen) {
        dropdown.classList.add('show');
        icon.className = 'fas fa-chevron-up';
    } else {
        dropdown.classList.remove('show');
        icon.className = 'fas fa-chevron-down';
    }
}

// Fonction pour mettre à jour le texte du dropdown selon les jeux sélectionnés
function updateJeuxSelection() {
    const checkboxes = document.querySelectorAll('input[name="jeux[]"]:checked');
    const selectedText = document.getElementById('jeuxSelectedText');

    if (checkboxes.length === 0) {
        selectedText.textContent = 'Tous les jeux';
    } else {
        selectedText.textContent = checkboxes.length + ' jeu' + (checkboxes.length > 1 ? 'x' : '') + ' sélectionné' + (checkboxes.length > 1 ? 's' : '');
    }
}

// Fermer dropdown en cliquant à l'extérieur (evenements.php)
document.addEventListener('click', function(event) {
    const dropdown = document.querySelector('.jeux-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        const jeuxDropdown = document.getElementById('jeuxDropdown');
        if (jeuxDropdown) {
            jeuxDropdown.classList.remove('show');
        }
        jeuxDropdownOpen = false;
        const icon = dropdown.querySelector('i');
        if (icon) icon.className = 'fas fa-chevron-down';
    }
});

/* ===================================
   JAVASCRIPT SPÉCIFIQUE À EVENEMENT-DETAIL.PHP
   =================================== */

// Fonction pour toggler la sélection d'une préférence de jeu
function togglePreference(jeuId) {
    const checkbox = document.getElementById(`jeu_${jeuId}`);
    const item = checkbox.closest('.preference-item');

    // Vérifier le nombre de jeux déjà sélectionnés
    const selectedCheckboxes = document.querySelectorAll('.preference-checkbox:checked');
    const selectedCount = selectedCheckboxes.length;

    // Si on veut cocher et qu'on a déjà 3 jeux sélectionnés, on bloque
    if (!checkbox.checked && selectedCount >= 3) {
        alert('Vous pouvez sélectionner au maximum 3 jeux.');
        return;
    }

    checkbox.checked = !checkbox.checked;

    if (checkbox.checked) {
        item.classList.add('selected');
    } else {
        item.classList.remove('selected');
    }

    // Mettre à jour l'état des autres éléments
    updatePreferencesState();
}

// Fonction pour mettre à jour l'état des préférences de jeux
function updatePreferencesState() {
    const checkboxes = document.querySelectorAll('.preference-checkbox');
    const selectedCount = document.querySelectorAll('.preference-checkbox:checked').length;

    // Mettre à jour le compteur si il existe
    let counter = document.getElementById('preferences-counter');
    if (!counter) {
        // Créer le compteur s'il n'existe pas
        counter = document.createElement('div');
        counter.id = 'preferences-counter';
        counter.className = 'preferences-counter';

        const preferencesGrid = document.querySelector('.preferences-grid');
        if (preferencesGrid) {
            preferencesGrid.parentNode.insertBefore(counter, preferencesGrid.nextSibling);
        }
    }

    counter.textContent = `${selectedCount}/3 jeux sélectionnés`;
    counter.classList.toggle('limit-reached', selectedCount >= 3);

    // Désactiver visuellement les éléments non sélectionnés si on a atteint la limite
    checkboxes.forEach(checkbox => {
        const item = checkbox.closest('.preference-item');
        if (!checkbox.checked && selectedCount >= 3) {
            item.classList.add('disabled');
            item.style.opacity = '0.5';
            item.style.pointerEvents = 'none';
        } else {
            item.classList.remove('disabled');
            item.style.opacity = '1';
            item.style.pointerEvents = 'auto';
        }
    });
}

// Initialisation spécifique pour evenement-detail.php
document.addEventListener('DOMContentLoaded', function() {

    // Rendre toute la zone de préférence cliquable (amélioration principale)
    document.querySelectorAll('.preference-item').forEach(item => {
        item.addEventListener('click', function(e) {
            // Ne pas déclencher si on clique directement sur la checkbox (éviter le double toggle)
            if (e.target.type === 'checkbox') {
                return;
            }

            // Ne pas déclencher si l'élément est désactivé
            if (this.classList.contains('disabled')) {
                return;
            }

            const checkbox = this.querySelector('.preference-checkbox');
            if (checkbox) {
                const jeuId = checkbox.value;
                togglePreference(jeuId);
            }
        });

        // Ajouter un style de cursor pointer pour indiquer que c'est cliquable
        item.style.cursor = 'pointer';
    });

    // Empêcher la propagation du clic quand on clique directement sur la checkbox
    document.querySelectorAll('.preference-checkbox').forEach(checkbox => {
        checkbox.addEventListener('click', function(e) {
            e.stopPropagation();

            const selectedCheckboxes = document.querySelectorAll('.preference-checkbox:checked');
            const selectedCount = selectedCheckboxes.length;

            // Si on veut cocher et qu'on a déjà 3 jeux sélectionnés, on bloque
            if (this.checked && selectedCount > 3) {
                this.checked = false;
                alert('Vous pouvez sélectionner au maximum 3 jeux.');
                return;
            }

            const item = this.closest('.preference-item');

            if (this.checked) {
                item.classList.add('selected');
            } else {
                item.classList.remove('selected');
            }

            updatePreferencesState();
        });
    });

    // Initialiser l'état des préférences au chargement
    document.querySelectorAll('.preference-checkbox:checked').forEach(checkbox => {
        checkbox.closest('.preference-item').classList.add('selected');
    });

    // Initialiser le compteur de préférences
    if (document.querySelector('.preference-checkbox')) {
        updatePreferencesState();
    }

});

document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form'); 
    const motDePasse = document.getElementById('mot_de_passe');
    const confirmerMotDePasse = document.getElementById('confirmer_mot_de_passe');
    
    function validatePasswords() {
        if (confirmerMotDePasse.value === '') {
            confirmerMotDePasse.classList.remove('password-match', 'password-mismatch');
            return null; 
        }
        
        if (motDePasse.value === confirmerMotDePasse.value) {
            confirmerMotDePasse.classList.remove('password-mismatch');
            confirmerMotDePasse.classList.add('password-match');
            return true; // Mots de passe correspondent
        } else {
            confirmerMotDePasse.classList.remove('password-match');
            confirmerMotDePasse.classList.add('password-mismatch');
            return false; // Mots de passe ne correspondent pas
        }
    }
    
    motDePasse.addEventListener('input', validatePasswords);
    confirmerMotDePasse.addEventListener('input', validatePasswords);
    
    form.addEventListener('submit', function(e) {
        const passwordsValid = validatePasswords();
        
        if (passwordsValid === false) {
            e.preventDefault(); // Empêche la soumission du formulaire
            const errorDiv = document.createElement('div');
            errorDiv.textContent = 'Les mots de passe ne correspondent pas';
            errorDiv.style.color = 'red';
            errorDiv.style.marginTop = '10px';
            confirmerMotDePasse.parentNode.appendChild(errorDiv);

            // Supprimer le message après 3 secondes
           setTimeout(() => {
         if (errorDiv.parentNode) {
        errorDiv.parentNode.removeChild(errorDiv);
          }
         }, 3000);
            
            // focus sur le champ de confirmation
            confirmerMotDePasse.focus();
            return false;
        }
    });
});

function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const toggleButton = passwordField.nextElementSibling;
    const eyeIcon = toggleButton.querySelector('.eye-icon');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        eyeIcon.textContent = '🙈';
    } else {
        passwordField.type = 'password';
        eyeIcon.textContent = '👁️';
    }
}