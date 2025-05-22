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
</script>
</body>
</html>