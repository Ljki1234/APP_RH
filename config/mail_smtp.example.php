<?php
/**
 * Configuration SMTP pour envoyer les e-mails avec VOTRE adresse.
 *
 * 1. Copiez ce fichier et renommez-le en : mail_smtp.php
 * 2. Remplissez les valeurs ci-dessous avec votre adresse et mot de passe d'application.
 * 3. Ne mettez pas mail_smtp.php dans Git (ajoutez-le à .gitignore).
 */

// Votre adresse e-mail (celle qui enverra les mails aux clients)
define('SMTP_FROM_EMAIL', 'votre.email@gmail.com');
define('SMTP_FROM_NAME', 'Gestion RH');

// Serveur SMTP
define('SMTP_HOST', 'smtp.gmail.com');   // Gmail : smtp.gmail.com  |  Outlook : smtp-mail.outlook.com
define('SMTP_PORT', 587);                // 587 pour TLS, 465 pour SSL
define('SMTP_SECURE', 'tls');            // 'tls' ou 'ssl'
define('SMTP_USER', 'votre.email@gmail.com');  // Même adresse que SMTP_FROM_EMAIL en général
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx');    // Mot de passe d'application (voir ci-dessous)

/**
 * Gmail : vous devez utiliser un "Mot de passe d'application", pas votre mot de passe normal.
 *   1. Allez sur https://myaccount.google.com/
 *   2. Sécurité → Validation en 2 étapes (activez-la si besoin)
 *   3. Mots de passe des applications → Générez un mot de passe pour "Mail"
 *   4. Copiez le mot de passe (16 caractères) dans SMTP_PASS ci-dessus.
 *
 * Outlook / Live : utilisez votre mot de passe ou un mot de passe d'application.
 * Autres fournisseurs : consultez leur doc SMTP (serveur, port, TLS/SSL).
 */
