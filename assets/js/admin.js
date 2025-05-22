document.addEventListener('DOMContentLoaded', function() {

    //Met à jour automatiquement l'affichage de la date de fin selon la durée sélectionnée
    function initEventDateManager() {
        const dateDebut = document.getElementById('date_debut');
        const dureeType = document.getElementById('duree_type');
        const dateFinInfo = document.getElementById('date_fin_info');

        // Vérifier que les éléments existent avant de continuer
        if (!dateDebut || !dureeType || !dateFinInfo) {
            return;
        }

        function updateDateFinInfo() {
            const dateDebutValue = dateDebut.value;
            const dureeValue = dureeType.value;

            if (dateDebutValue && dureeValue) {
                const date = new Date(dateDebutValue);
                let dateFin = new Date(date);

                // Calculer la date de fin selon la durée
                if (dureeValue === 'weekend') {
                    dateFin.setDate(date.getDate() + 1);
                }

                // Options de formatage pour l'affichage en français
                const options = { day: 'numeric', month: 'long', year: 'numeric' };
                const dateDebutFormatted = date.toLocaleDateString('fr-FR', options);
                const dateFinFormatted = dateFin.toLocaleDateString('fr-FR', options);

                // Afficher le texte selon la durée
                if (dureeValue === 'demi-journée' || dureeValue === 'journée') {
                    dateFinInfo.textContent = `Date de fin automatique : ${dateDebutFormatted}`;
                } else {
                    dateFinInfo.textContent = `Date de fin automatique : ${dateFinFormatted}`;
                }
                dateFinInfo.style.color = '#28a745';
            } else {
                dateFinInfo.textContent = '';
            }
        }

        // Attacher les événements
        dateDebut.addEventListener('change', updateDateFinInfo);
        dureeType.addEventListener('change', updateDateFinInfo);

        // Initialiser l'affichage au chargement (utile pour la modification)
        updateDateFinInfo();
    }

    // Gère la prévisualisation d'images pour les formulaires d'upload
    function initImagePreview() {
        const imageInput = document.getElementById('image');
        const preview = document.getElementById('preview');

        // Vérifier que les éléments existent avant de continuer
        if (!imageInput || !preview) {
            return;
        }

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file) {
                // Vérifier que c'est bien une image
                if (!file.type.startsWith('image/')) {
                    preview.style.display = 'none';
                    alert('Veuillez sélectionner un fichier image valide.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    }

    //Ajoute des confirmations personnalisés pour les suppressions
    function initDeleteConfirmations() {
        const deleteLinks = document.querySelectorAll('a[href*="supprimer.php"]');
        
        deleteLinks.forEach(function(link) {
            // Récupérer le type d'élément à supprimer depuis l'URL ou le contexte
            let itemType = 'cet élément';
            if (link.href.includes('/jeux/')) {
                itemType = 'ce jeu';
            } else if (link.href.includes('/evenements/')) {
                itemType = 'cet événement';
            } else if (link.href.includes('/inscriptions/')) {
                itemType = 'cette inscription';
            }

            link.addEventListener('click', function(e) {
                const confirmed = confirm(`Êtes-vous sûr de vouloir supprimer ${itemType} ?\n\nCette action est irréversible.`);
                if (!confirmed) {
                    e.preventDefault();
                }
            });
        });
    }

    //Masque automatiquement les alertes après 5 secondes
    function initAutoHideAlerts() {
        const successAlerts = document.querySelectorAll('.alert-success');
        
        successAlerts.forEach(function(alert) {
            // Masquer automatiquement après 5 secondes
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.remove();
                        }
                    }, 500);
                }
            }, 5000); //5000ms = 5sec
        });
    }

    //Amélioration UX des formulaires
    function initFormEnhancements() {
        // Sauvegarde automatique des données du formulaire en cas de rafraîchissement accidentel
        const forms = document.querySelectorAll('form[method="POST"], form[method="post"]');
        
        forms.forEach(function(form) {
            const formId = form.action || window.location.pathname;
            
            // Restaurer les données sauvegardées
            const savedData = localStorage.getItem('form_backup_' + formId);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    Object.keys(data).forEach(function(name) {
                        const input = form.querySelector(`[name="${name}"]`);
                        if (input && input.type !== 'password' && input.type !== 'file') {
                            input.value = data[name];
                        }
                    });
                    // Supprimer la sauvegarde après restauration
                    localStorage.removeItem('form_backup_' + formId);
                } catch (e) {
                    // Ignorer les erreurs de parsing
                }
            }

            // Sauvegarder les données avant soumission
            form.addEventListener('submit', function() {
                localStorage.removeItem('form_backup_' + formId);
            });

            // Sauvegarder périodiquement les données
            let saveTimeout;
            form.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    const formData = new FormData(form);
                    const data = {};
                    for (let [name, value] of formData.entries()) {
                        if (form.querySelector(`[name="${name}"]`).type !== 'password' && 
                            form.querySelector(`[name="${name}"]`).type !== 'file') {
                            data[name] = value;
                        }
                    }
                    localStorage.setItem('form_backup_' + formId, JSON.stringify(data));
                }, 1000);
            });
        });
    }

    // Initialiser tous les modules
    initEventDateManager();
    initImagePreview();
    initDeleteConfirmations();
    initAutoHideAlerts();
    initFormEnhancements();

    // Afficher un message de debug en mode développement
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log('Admin.js chargé avec succès - Modules initialisés');
    }
});

//Les fonctions en dessous sont utilitaires
//Utilitaire pour formater les dates en français
window.AdminUtils = {
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    },

    //Utilitaire pour valider les emails
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    //Utilitaire pour afficher des notifications toast
    showToast: function(message, type = 'info') {
        // Créer un toast Bootstrap ou une notification simple
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed`;
        toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close ms-2" onclick="this.parentElement.remove()"></button>
        `;
        
        document.body.appendChild(toast);
        
        // Supprimer automatiquement après 5 secondes
        setTimeout(function() {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }
};