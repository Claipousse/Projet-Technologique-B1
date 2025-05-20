</main>
    
    <footer class="mt-5 py-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Pistache</h5>
                    <p>Votre boutique spécialisée en jeux de société à Bordeaux. Découvrez notre vaste collection de jeux pour tous les âges et tous les goûts.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Navigation</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-decoration-none text-white">Accueil</a></li>
                        <li><a href="catalogue.php" class="text-decoration-none text-white">Catalogue</a></li>
                        <li><a href="evenements.php" class="text-decoration-none text-white">Événements</a></li>
                        <?php if (estConnecte()): ?>
                            <li><a href="profil.php" class="text-decoration-none text-white">Mon profil</a></li>
                        <?php else: ?>
                            <li><a href="connexion.php" class="text-decoration-none text-white">Connexion</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <address>
                        <p><i class="bi bi-geo-alt-fill me-2"></i> 123 Rue des Jeux, 33000 Bordeaux</p>
                        <p><i class="bi bi-telephone-fill me-2"></i> 05 56 XX XX XX</p>
                        <p><i class="bi bi-envelope-fill me-2"></i> <a href="mailto:contact@pistache-jeux.fr" class="text-white">contact@pistache-jeux.fr</a></p>
                    </address>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-2"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="bi bi-instagram"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4 bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Pistache. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS personnalisé -->
    <script src="assets/js/main.js"></script>
</body>
</html>