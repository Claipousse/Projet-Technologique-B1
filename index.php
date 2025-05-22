<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pistache - Boutique de Jeux de Société</title>
  <link
          rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
  />
  <link
          rel="stylesheet"
          href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lora:wght@400;500&display=swap"
  />
  <style>
    /* tout le CSS existant de Clément ici */
    :root {
      --primary-color: #8b4513;
      --secondary-color: #5d4037;
      --accent-color: #d2b48c;
      --light-bg: #f5f5dc;
      --dark-text: #3e2723;
      --light-text: #fff8e1;
    }
    /* ... (garde tout le reste du style, déjà prêt) ... */
  </style>
</head>

<body>
<?php
    // Si l’utilisateur vient d’une inscription avec ?inscription=ok, on affiche un message
    if (isset($_GET['inscription']) && $_GET['inscription'] === 'ok'): ?>
<div id="welcomeMessage" style="background-color: #d2b48c; color: #3e2723; text-align: center; padding: 1rem; margin-top: 100px; font-weight: bold; border: 2px solid #8b4513;">
  🥳 Ton compte a bien été créé ! Bienvenue chez Pistache 💛
</div>
<script>
  setTimeout(function () {
    const msg = document.getElementById("welcomeMessage");
    if (msg) {
      msg.style.transition = "opacity 0.5s ease";
      msg.style.opacity = "0";
      setTimeout(() => msg.remove(), 500);
    }
  }, 5000);
</script>
<?php endif; ?>

<!-- Tout le reste du contenu est celui de Clément -->
<header>
  <div class="logo">
    <img src="media/images/pistache-logo.png" alt="Logo Pistache" />
    Pistache
  </div>
  <button class="menu-toggle" id="menuToggle">
    <i class="fas fa-bars"></i>
  </button>
  <nav>
    <ul id="mainMenu">
      <li><a href="index.php">Accueil</a></li>
      <li><a href="catalogue.html">Catalogue</a></li>
      <li><a href="evenements.html">Événements</a></li>
      <li><a href="contact.html">Contact</a></li>
      <li><a href="panier.html">Panier</a></li>
    </ul>
  </nav>
</header>

<!-- Toutes les sections de la page : accueil, catalogue, événements, etc. -->
<!-- ... colle ici tout le reste du code HTML inchangé de ton ancien index.php ... -->

<footer>
  <p>&copy; 2025 Pistache. Tous droits réservés.</p>
</footer>

<script>
  document.getElementById("menuToggle").addEventListener("click", function () {
    document.getElementById("mainMenu").classList.toggle("active");
  });

  document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      e.preventDefault();
      document.querySelector(this.getAttribute("href")).scrollIntoView({
        behavior: "smooth",
      });

      if (document.getElementById("mainMenu").classList.contains("active")) {
        document.getElementById("mainMenu").classList.remove("active");
      }
    });
  });
</script>
</body>
</html>
