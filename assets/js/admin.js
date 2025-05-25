/**
 * Admin.js - Script JavaScript simplifié pour l'administration Pistache
 */

document.addEventListener('DOMContentLoaded', function() {

    // Configuration simple
    const isDevMode = ['localhost', '127.0.0.1'].includes(window.location.hostname);

    /**
     * Gestionnaire des dates d'événements
     * Met à jour automatiquement l'affichage de la date de fin selon la durée sélectionnée
     */
    function initEventDateManager() {
        const dateDebut = document.getElementById('date_debut');
        const dureeType = document.getElementById('duree_type');
        const dateFinInfo = document.getElementById('date_fin_info');

        // Vérifier que les éléments existent
        if (!dateDebut || !dureeType || !dateFinInfo) {
            return;
        }

        function updateDateFinInfo() {
            const dateDebutValue = dateDebut.value;
            const dureeValue = dureeType.value;

            if (!dateDebutValue || !dureeValue) {
                dateFinInfo.textContent = '';
                return;
            }

            const date = new Date(dateDebutValue);
            let dateFin = new Date(date);

            // Calculer la date de fin selon la durée
            if (dureeValue === 'weekend') {
                dateFin.setDate(date.getDate() + 1);
            }

            // Formater les dates en français
            const optionsFr = { day: 'numeric', month: 'long', year: 'numeric' };
            const dateDebutFormatted = date.toLocaleDateString('fr-FR', optionsFr);
            const dateFinFormatted = dateFin.toLocaleDateString('fr-FR', optionsFr);

            // Afficher le texte selon la durée
            const message = dureeValue === 'weekend'
                ? `Date de fin automatique : ${dateFinFormatted}`
                : `Date de fin automatique : ${dateDebutFormatted}`;

            dateFinInfo.textContent = message;
            dateFinInfo.style.color = '#28a745';
        }

        // Attacher les événements
        dateDebut.addEventListener('change', updateDateFinInfo);
        dureeType.addEventListener('change', updateDateFinInfo);

        // Initialiser l'affichage au chargement
        updateDateFinInfo();
    }

    /**
     * Gestionnaire de prévisualisation d'images
     */
    function initImagePreview() {
        const imageInput = document.getElementById('image');
        const preview = document.getElementById('preview');

        if (!imageInput || !preview) {
            return;
        }

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];

            if (!file) {
                preview.style.display = 'none';
                return;
            }

            // Vérifier que c'est une image
            if (!file.type.startsWith('image/')) {
                preview.style.display = 'none';
                alert('Veuillez sélectionner un fichier image valide.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
                preview.style.maxWidth = '200px';
                preview.style.height = 'auto';
                preview.style.borderRadius = '4px';
            };
            reader.readAsDataURL(file);
        });
    }

    /**
     * Gestionnaire des confirmations de suppression
     */
    function initDeleteConfirmations() {
        const deleteLinks = document.querySelectorAll('a[href*="supprimer.php"]');

        deleteLinks.forEach(function(link) {
            // Déterminer le type d'élément à supprimer
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

    /**
     * Masque automatiquement les alertes de succès après 5 secondes
     */
    function initAutoHideAlerts() {
        const successAlerts = document.querySelectorAll('.alert-success');

        successAlerts.forEach(function(alert) {
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
            }, 5000); // 5 secondes
        });
    }

    /**
     * Sauvegarde automatique des formulaires pour éviter la perte de données
     */
    function initFormBackup() {
        const forms = document.querySelectorAll('form[method="POST"], form[method="post"]');

        forms.forEach(function(form) {
            const formId = btoa(form.action || window.location.pathname).slice(0, 16);
            const backupKey = `form_backup_${formId}`;

            // Restaurer les données sauvegardées
            const savedData = localStorage.getItem(backupKey);
            if (savedData) {
                try {
                    const data = JSON.parse(savedData);
                    Object.keys(data).forEach(function(name) {
                        const input = form.querySelector(`[name="${name}"]`);
                        if (input && input.type !== 'password' && input.type !== 'file') {
                            input.value = data[name];
                        }
                    });
                    localStorage.removeItem(backupKey);
                } catch (e) {
                    // Ignorer les erreurs de parsing
                }
            }

            // Nettoyer la sauvegarde à la soumission
            form.addEventListener('submit', function() {
                localStorage.removeItem(backupKey);
            });

            // Sauvegarder périodiquement
            let saveTimeout;
            form.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(function() {
                    const formData = new FormData(form);
                    const data = {};
                    for (let [name, value] of formData.entries()) {
                        const field = form.querySelector(`[name="${name}"]`);
                        if (field && field.type !== 'password' && field.type !== 'file') {
                            data[name] = value;
                        }
                    }
                    try {
                        localStorage.setItem(backupKey, JSON.stringify(data));
                    } catch (e) {
                        // Ignorer les erreurs de stockage
                    }
                }, 1000);
            });
        });
    }

    // Initialiser tous les modules
    initEventDateManager();
    initImagePreview();
    initDeleteConfirmations();
    initAutoHideAlerts();
    initFormBackup();

    // Message de debug en mode développement
    if (isDevMode) {
        console.log('Admin.js chargé avec succès');
    }
});

/**
 * Utilitaires globaux simplifiés
 */
window.AdminUtils = {
    /**
     * Formate une date en français
     */
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });
    },

    /**
     * Valide un email
     */
    isValidEmail: function(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    },

    /**
     * Affiche une notification simple
     */
    showToast: function(message, type = 'info') {
        // Supprimer les anciens toasts
        const existingToasts = document.querySelectorAll('.admin-toast');
        existingToasts.forEach(toast => toast.remove());

        const toast = document.createElement('div');
        toast.className = `alert alert-${type} admin-toast position-fixed`;
        toast.style.cssText = `
            top: 20px; 
            right: 20px; 
            z-index: 9999; 
            min-width: 300px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
        `;

        toast.innerHTML = `
            ${message}
            <button type="button" class="btn-close ms-2" onclick="this.parentElement.remove()"></button>
        `;

        document.body.appendChild(toast);

        // Supprimer automatiquement après 5 secondes
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 5000);
    }
};