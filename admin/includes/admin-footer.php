</main>
<!-- Fin du contenu principal -->

<!-- Footer de l'admin -->
<footer class="footer mt-auto py-3 bg-dark text-white">
    <div class="container text-center">
        <p class="mb-0">
            &copy; <?php echo date('Y'); ?> Pistache - Administration
        </p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- JavaScript centralisé pour l'administration -->
<?php
// Déterminer le chemin vers admin.js selon la profondeur
$currentPath = $_SERVER['REQUEST_URI'];
if (strpos($currentPath, '/admin/jeux/') !== false || strpos($currentPath, '/admin/evenements/') !== false || strpos($currentPath, '/admin/inscriptions/') !== false) {
    $jsPath = '../assets/js/admin.js';
} else {
    $jsPath = 'assets/js/admin.js';
}
?>
<script src="<?php echo $jsPath; ?>"></script>

</body>
</html>