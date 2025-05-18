/**
 * admin.js - Scripts pour le panneau d'administration de Pistache
 * Partie du projet technologique - Application Web pour une boutique de jeux de société
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialiser les popovers Bootstrap
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl, {
            html: true
        });
    });

    // Gestion de la confirmation de suppression
    const confirmDelete = document.querySelectorAll('.confirm-delete');
    confirmDelete.forEach(button => {
        button.addEventListener('click', function(event) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                event.preventDefault();
            }
        });
    });

    // Fonction pour limiter le nombre de sélections dans les checkboxes
    function limitCheckboxes(name, maxChecked) {
        const checkboxes = document.querySelectorAll(`input[name="${name}"]`);
        let checkedCount = 0;

        checkboxes.forEach(function(checkbox) {
            if (checkbox.checked) checkedCount++;

            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    checkedCount++;
                    if (checkedCount > maxChecked) {
                        this.checked = false;
                        checkedCount--;
                        alert(`Vous ne pouvez pas sélectionner plus de ${maxChecked} options.`);
                    }
                } else {
                    checkedCount--;
                }
            });
        });
    }

    // Limiter à 3 jeux pour les préférences
    if (document.querySelector('input[name="preferences[]"]')) {
        limitCheckboxes('preferences[]', 3);
    }

    // Gestion des filtres avec mise à jour automatique
    const autoSubmitFilters = document.querySelectorAll('.auto-submit');
    autoSubmitFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            this.closest('form').submit();
        });
    });

    // Toggle sections repliables
    const collapsibleHeaders = document.querySelectorAll('.collapsible-header');
    collapsibleHeaders.forEach(header => {
        header.addEventListener('click', function() {
            this.classList.toggle('active');
            const content = this.nextElementSibling;
            if (content.style.maxHeight) {
                content.style.maxHeight = null;
            } else {
                content.style.maxHeight = content.scrollHeight + "px";
            }
        });
    });

    // Fonctionnalité de recherche instantanée pour les tableaux
    function setupTableSearch(inputId, tableId) {
        const searchInput = document.getElementById(inputId);
        if (!searchInput) return;

        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const table = document.getElementById(tableId);
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.indexOf(searchText) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Initialiser le recherche pour différentes tables
    setupTableSearch('searchJeux', 'tableJeux');
    setupTableSearch('searchEvenements', 'tableEvenements');
    setupTableSearch('searchUtilisateurs', 'tableUtilisateurs');
    setupTableSearch('searchInscriptions', 'tableInscriptions');

    // Gestion de l'affichage des champs de mot de passe
    const togglePassword = document.getElementById('changer_mdp');
    if (togglePassword) {
        togglePassword.addEventListener('change', function() {
            const passwordFields = document.getElementById('password_fields');
            if (this.checked) {
                passwordFields.style.display = 'block';
            } else {
                passwordFields.style.display = 'none';
            }
        });
    }

    // Prévisualisation d'image pour l'upload
    const imageUpload = document.getElementById('image_upload');
    if (imageUpload) {
        imageUpload.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image_preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Système d'onglets pour les formulaires complexes
    const tabButtons = document.querySelectorAll('.tab-button');
    if (tabButtons.length > 0) {
        tabButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Désactiver tous les onglets
                tabButtons.forEach(btn => btn.classList.remove('active'));
                
                // Cacher tous les contenus d'onglets
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                // Activer l'onglet cliqué
                this.classList.add('active');
                
                // Afficher le contenu correspondant
                const targetTab = this.getAttribute('data-tab');
                document.getElementById(targetTab).style.display = 'block';
            });
        });
        
        // Activer le premier onglet par défaut
        tabButtons[0].click();
    }

    // Fonction pour activer/désactiver des champs en fonction d'une checkbox
    function toggleFieldsVisibility(checkboxId, targetFieldsSelector, invert = false) {
        const checkbox = document.getElementById(checkboxId);
        if (!checkbox) return;

        const updateVisibility = function() {
            const fieldsToToggle = document.querySelectorAll(targetFieldsSelector);
            fieldsToToggle.forEach(field => {
                if (invert) {
                    field.style.display = checkbox.checked ? 'none' : 'block';
                } else {
                    field.style.display = checkbox.checked ? 'block' : 'none';
                }
            });
        };

        // Initialiser l'état
        updateVisibility();
        
        // Ajouter l'écouteur d'événement
        checkbox.addEventListener('change', updateVisibility);
    }

    // Gestion du compteur de caractères pour les textarea
    const textareas = document.querySelectorAll('.character-counter');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        if (!maxLength) return;

        // Créer le compteur
        const counter = document.createElement('div');
        counter.className = 'text-muted small text-end';
        counter.innerHTML = `0/${maxLength} caractères`;
        textarea.parentNode.insertBefore(counter, textarea.nextSibling);

        // Mettre à jour le compteur
        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.innerHTML = `${currentLength}/${maxLength} caractères`;
        });
    });

    // Gestion des dates dans les formulaires d'événements
    const dateDebut = document.getElementById('date_debut');
    const dateFin = document.getElementById('date_fin');
    if (dateDebut && dateFin) {
        dateDebut.addEventListener('change', function() {
            // Assurer que la date de fin est au moins égale à la date de début
            if (dateFin.value && dateFin.value < dateDebut.value) {
                dateFin.value = dateDebut.value;
            }
            // Mettre à jour la date minimale pour la date de fin
            dateFin.min = dateDebut.value;
        });
    }

    // Animation de chargement pour les actions qui prennent du temps
    const loadingButtons = document.querySelectorAll('.btn-loading');
    loadingButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Sauvegarder le texte d'origine
            if (!button.getAttribute('data-original-text')) {
                button.setAttribute('data-original-text', button.innerHTML);
            }
            
            // Changer l'apparence pendant le chargement
            button.disabled = true;
            button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Chargement...';
            
            // Rétablir l'apparence après le chargement (simulé ici)
            setTimeout(() => {
                button.innerHTML = button.getAttribute('data-original-text');
                button.disabled = false;
            }, 5000); // Ceci est juste pour la démonstration
        });
    });
});
