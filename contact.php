<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contact - Pistache</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
    />
    <link
      rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Lora:wght@400;500&display=swap"
    />
    <style>
      :root {
        --primary-color: #8b4513; /* Marron */
        --secondary-color: #5d4037; /* Marron foncé */
        --accent-color: #d2b48c; /* Tan */
        --light-bg: #f5f5dc; /* Beige */
        --dark-text: #3e2723; /* Marron très foncé */
        --light-text: #fff8e1; /* Ivoire */
      }

      * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: "Lora", Georgia, serif;
      }

      body {
        background-color: var(--light-bg);
        color: var(--dark-text);
      }

      header {
        background: var(--secondary-color);
        color: var(--light-text);
        padding: 1rem 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        position: fixed;
        width: 100%;
        z-index: 1000;
        border-bottom: 2px solid var(--accent-color);
      }

      .logo {
        font-size: 1.5rem;
        font-weight: bold;
        display: flex;
        align-items: center;
      }

      .logo img {
        height: 50px;
        margin-left: 8px;
      }

      nav ul {
        display: flex;
        list-style: none;
        gap: 1.5rem;
      }

      nav a {
        color: var(--light-text);
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 4px;
        transition: background-color 0.3s;
        font-family: "Playfair Display", serif;
        letter-spacing: 1px;
      }

      nav a:hover {
        background-color: var(--primary-color);
        color: var(--light-text);
      }

      .menu-toggle {
        display: none;
        background: none;
        border: none;
        color: var(--light-text);
        font-size: 1.5rem;
        cursor: pointer;
        padding: 0.5rem;
      }

      main {
        padding-top: 80px;
        min-height: 100vh;
        padding-bottom: 2rem;
      }

      .contact-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 2rem;
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
      }

      .contact-info {
        flex: 1;
        min-width: 300px;
        background-color: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--accent-color);
      }

      .contact-form-container {
        flex: 2;
        min-width: 300px;
        background-color: white;
        border-radius: 8px;
        padding: 2rem;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--accent-color);
      }

      .page-title {
        font-family: "Playfair Display", serif;
        color: var(--primary-color);
        font-size: 2.5rem;
        margin-bottom: 1.5rem;
        text-align: center;
        position: relative;
        padding-bottom: 1rem;
      }

      .page-title::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 2px;
        background-color: var(--primary-color);
      }

      .info-title {
        font-family: "Playfair Display", serif;
        color: var(--primary-color);
        font-size: 1.5rem;
        margin-bottom: 1rem;
        border-bottom: 2px solid var(--accent-color);
        padding-bottom: 0.5rem;
      }

      .info-list {
        list-style: none;
      }

      .info-item {
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
      }

      .info-icon {
        margin-right: 1rem;
        font-size: 1.2rem;
        color: var(--primary-color);
        width: 20px;
        text-align: center;
      }

      .info-content {
        flex: 1;
      }

      .info-label {
        font-weight: bold;
        margin-bottom: 0.3rem;
        color: var(--dark-text);
      }

      .info-text {
        color: #666;
        line-height: 1.4;
      }

      .social-links {
        margin-top: 2rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
      }

      .social-link {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--primary-color);
        color: var(--light-text);
        text-decoration: none;
        transition: all 0.3s;
      }

      .social-link:hover {
        background-color: var(--secondary-color);
        transform: translateY(-3px);
      }

      .form-group {
        margin-bottom: 1.5rem;
      }

      .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: bold;
        color: var(--dark-text);
      }

      .form-control {
        width: 100%;
        padding: 0.8rem;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-family: inherit;
        font-size: 1rem;
        transition: border-color 0.3s;
      }

      .form-control:focus {
        outline: none;
        border-color: var(--accent-color);
        box-shadow: 0 0 0 2px rgba(210, 180, 140, 0.3);
      }

      textarea.form-control {
        min-height: 150px;
        resize: vertical;
      }

      .btn-submit {
        display: inline-block;
        padding: 0.8rem 1.8rem;
        background-color: var(--primary-color);
        color: var(--light-text);
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
        letter-spacing: 1px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        border: 1px solid var(--light-text);
        cursor: pointer;
        font-family: "Playfair Display", serif;
      }

      .btn-submit:hover {
        background-color: var(--secondary-color);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
      }

      .map-container {
        width: 100%;
        margin-top: 2rem;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid var(--accent-color);
      }

      .map-container iframe {
        width: 100%;
        height: 300px;
        border: none;
      }

      .alert {
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-weight: bold;
      }

      .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
      }

      .alert-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
      }

      footer {
        background-color: var(--secondary-color);
        color: var(--light-text);
        text-align: center;
        padding: 1.5rem;
        margin-top: 2rem;
        border-top: 3px solid var(--primary-color);
        font-family: "Playfair Display", Georgia, serif;
        font-size: 0.9rem;
        letter-spacing: 1px;
      }

      @media (max-width: 992px) {
        .menu-toggle {
          display: block;
        }

        nav ul {
          position: fixed;
          top: 70px;
          right: -300px;
          width: 250px;
          height: 100vh;
          background-color: var(--secondary-color);
          flex-direction: column;
          padding: 2rem 1rem;
          transition: right 0.3s ease;
          box-shadow: -2px 0 10px rgba(0, 0, 0, 0.2);
          z-index: 1000;
        }

        nav ul.active {
          right: 0;
        }

        nav ul li {
          margin-bottom: 1rem;
        }

        nav a {
          display: block;
          padding: 0.8rem;
          width: 100%;
          text-align: center;
        }
      }
    </style>
  </head>
  <body>
    <?php
    // Initialiser les variables
    $nom = $email = $sujet = $message = "";
    $errors = [];
    $success_message = "";
    
    // Traitement du formulaire lors de la soumission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validation du nom
        if (empty($_POST["nom"])) {
            $errors[] = "Le nom est obligatoire";
        } else {
            $nom = filter_var($_POST["nom"], FILTER_SANITIZE_STRING);
        }
        
        // Validation de l'email
        if (empty($_POST["email"])) {
            $errors[] = "L'email est obligatoire";
        } else {
            $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format d'email invalide";
            }
        }
        
        // Validation du sujet
        if (empty($_POST["sujet"])) {
            $errors[] = "Le sujet est obligatoire";
        } else {
            $sujet = filter_var($_POST["sujet"], FILTER_SANITIZE_STRING);
        }
        
        // Validation du message
        if (empty($_POST["message"])) {
            $errors[] = "Le message est obligatoire";
        } else {
            $message = filter_var($_POST["message"], FILTER_SANITIZE_STRING);
        }
        
        // Si pas d'erreurs, traiter l'envoi du message
        if (empty($errors)) {
            // Destinataire de l'email
            $to = "contact@pistache-jeux.fr";
            
            // Sujet de l'email
            $email_subject = "Nouveau message de contact: $sujet";
            
            // Corps de l'email
            $email_body = "Vous avez reçu un nouveau message de contact.\n\n";
            $email_body .= "Nom: $nom\n";
            $email_body .= "Email: $email\n";
            $email_body .= "Sujet: $sujet\n";
            $email_body .= "Message:\n$message\n";
            
            // En-têtes de l'email
            $headers = "From: $email\r\n";
            $headers .= "Reply-To: $email\r\n";
            
            // Envoi de l'email
            if (mail($to, $email_subject, $email_body, $headers)) {
                $success_message = "Votre message a été envoyé avec succès. Nous vous répondrons dans les plus brefs délais.";
                // Réinitialiser les champs du formulaire
                $nom = $email = $sujet = $message = "";
            } else {
                $errors[] = "Une erreur s'est produite lors de l'envoi du message. Veuillez réessayer.";
            }
        }
    }
    ?>

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
          <li><a href="contact.php">Contact</a></li>
          <li><a href="panier.html">Panier</a></li>
        </ul>
      </nav>
    </header>

    <main>
      <h1 class="page-title">Contactez-nous</h1>
      
      <div class="contact-container">
        <div class="contact-info">
          <h2 class="info-title">Nos coordonnées</h2>
          <ul class="info-list">
            <li class="info-item">
              <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
              <div class="info-content">
                <div class="info-label">Adresse</div>
                <div class="info-text">42 Rue des Jeux, 33000 Bordeaux</div>
              </div>
            </li>
            <li class="info-item">
              <div class="info-icon"><i class="fas fa-phone"></i></div>
              <div class="info-content">
                <div class="info-label">Téléphone</div>
                <div class="info-text">05 56 XX XX XX</div>
              </div>
            </li>
            <li class="info-item">
              <div class="info-icon"><i class="fas fa-envelope"></i></div>
              <div class="info-content">
                <div class="info-label">Email</div>
                <div class="info-text"> <a href="mailto:contact@pistache-jeux.fr" style="text-decoration:none;">contact@pistache-jeux.fr</a></div>
              </div>
            </li>
            <li class="info-item">
              <div class="info-icon"><i class="fas fa-clock"></i></div>
              <div class="info-content">
                <div class="info-label">Horaires d'ouverture</div>
                <div class="info-text">
                  Lundi - Vendredi: 10h - 19h<br />
                  Samedi: 10h - 20h<br />
                  Dimanche: 14h - 18h
                </div>
              </div>
            </li>
          </ul>
          
          <div class="social-links">
            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-link"><i class="fab fa-discord"></i></a>
          </div>
          
          <div class="map-container">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2828.9677257342667!2d-0.5793990845566641!3d44.84131198349898!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd5527e8f751ca81%3A0x796386037b397a89!2sBordeaux%2C%20France!5e0!3m2!1sfr!2sfr!4v1620042453258!5m2!1sfr!2sfr" allowfullscreen="" loading="lazy"></iframe>
          </div>
        </div>
        
        <div class="contact-form-container">
          <h2 class="info-title">Envoyez-nous un message</h2>
          
          <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
              <?php echo $success_message; ?>
            </div>
          <?php endif; ?>
          
          <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
              <ul>
                <?php foreach ($errors as $error): ?>
                  <li><?php echo $error; ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>
          
          <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
              <label for="nom" class="form-label">Nom</label>
              <input type="text" id="nom" name="nom" class="form-control" value="<?php echo $nom; ?>" required>
            </div>
            
            <div class="form-group">
              <label for="email" class="form-label">Email</label>
              <input type="email" id="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
            </div>
            
            <div class="form-group">
              <label for="sujet" class="form-label">Sujet</label>
              <input type="text" id="sujet" name="sujet" class="form-control" value="<?php echo $sujet; ?>" required>
            </div>
            
            <div class="form-group">
              <label for="message" class="form-label">Message</label>
              <textarea id="message" name="message" class="form-control" required><?php echo $message; ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Envoyer le message</button>
          </form>
        </div>
      </div>
    </main>

    <footer>
      <p>&copy; 2025 Pistache. Tous droits réservés.</p>
    </footer>

    <script>
      document
        .getElementById("menuToggle")
        .addEventListener("click", function () {
          document.getElementById("mainMenu").classList.toggle("active");
        });
    </script>
  </body>
</html>
