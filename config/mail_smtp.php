<?php
/**
 * Configuration SMTP - Envoi des e-mails depuis l'adresse admin
 * Gestion RH
 */

// Adresse e-mail admin (envoi des mails : mot de passe oublié, notifications congés, etc.)
define('SMTP_FROM_EMAIL', 'eljabikihind@gmail.com');
define('SMTP_FROM_NAME', 'Gestion RH');

// Serveur SMTP Gmail
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'eljabikihind@gmail.com');
define('SMTP_PASS', 'oghn fkaq pmdb ywxb');

/**
 * Si Gmail refuse (erreur d'authentification), utilisez un "Mot de passe d'application" :
 * 1. https://myaccount.google.com/ → Sécurité
 * 2. Activez la "Validation en 2 étapes" si besoin
 * 3. "Mots de passe des applications" → Générez un mot de passe pour "Mail"
 * 4. Collez les 16 caractères ici dans SMTP_PASS (sans espaces ou avec, les deux marchent)
 */
