</main>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Pistache. Tous droits réservés.</p>
</footer>

<script>
    // Menu mobile toggle
    document.getElementById("menuToggle")?.addEventListener("click", function () {
        document.getElementById("mainMenu").classList.toggle("active");
    });

    // Smooth scroll pour les liens d'ancres
    document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
        anchor.addEventListener("click", function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute("href"));
            if (target) {
                target.scrollIntoView({
                    behavior: "smooth",
                });
            }

            // Fermer le menu mobile si ouvert
            const menu = document.getElementById("mainMenu");
            if (menu?.classList.contains("active")) {
                menu.classList.remove("active");
            }
        });
    });

    // Solution JavaScript pour le menu déroulant
    document.addEventListener('DOMContentLoaded', function() {
        const dropdown = document.querySelector('.dropdown');
        const dropdownToggle = document.querySelector('.dropdown-toggle');
        const dropdownContent = document.querySelector('.dropdown-content');

        if (!dropdown || !dropdownToggle || !dropdownContent) return;

        let timeoutId;

        // Fonction pour afficher le menu
        function showDropdown() {
            clearTimeout(timeoutId);
            dropdownContent.style.display = 'block';
        }

        // Fonction pour cacher le menu avec délai
        function hideDropdown() {
            timeoutId = setTimeout(() => {
                dropdownContent.style.display = 'none';
            }, 100); // 100ms de délai pour laisser le temps de passer au menu
        }

        // Événements sur le bouton
        dropdownToggle.addEventListener('mouseenter', showDropdown);
        dropdownToggle.addEventListener('mouseleave', hideDropdown);

        // Événements sur le menu déroulant
        dropdownContent.addEventListener('mouseenter', showDropdown);
        dropdownContent.addEventListener('mouseleave', hideDropdown);

        // Clic en dehors pour fermer
        document.addEventListener('click', function(event) {
            if (!dropdown.contains(event.target)) {
                dropdownContent.style.display = 'none';
            }
        });

        // Toggle au clic sur le bouton
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            if (dropdownContent.style.display === 'block') {
                dropdownContent.style.display = 'none';
            } else {
                dropdownContent.style.display = 'block';
            }
        });
    });
</script>
</body>
</html>