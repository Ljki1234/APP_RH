# Envoyer des e-mails avec VOTRE adresse (Gmail, Outlook, etc.)

Pour que l’application envoie les e-mails **depuis votre adresse** (par exemple aux employés quand un congé est approuvé ou refusé), suivez ces étapes.

---

## 1. Installer PHPMailer

Ouvrez un **invite de commandes** (ou PowerShell) dans le dossier du projet :

```bash
cd c:\xampp\htdocs\Gestion_RH
composer install
```

Si `composer` n’est pas reconnu, installez Composer : https://getcomposer.org/download/

En cas d’erreur de type "Could not delete vendor/...", fermez l’éditeur/antivirus, supprimez le dossier `vendor` s’il existe, puis relancez `composer install`.

---

## 2. Créer votre fichier de configuration SMTP

1. Dans le dossier **config**, copiez le fichier **mail_smtp.example.php**.
2. Renommez la copie en : **mail_smtp.php**
3. Ouvrez **mail_smtp.php** et modifiez les valeurs suivantes.

---

## 3. Renseigner votre adresse et le mot de passe d’application

### Avec Gmail

- **SMTP_FROM_EMAIL** et **SMTP_USER** : votre adresse Gmail (ex. `monentreprise@gmail.com`).
- **SMTP_FROM_NAME** : le nom affiché (ex. `Gestion RH` ou le nom de votre société).
- **SMTP_HOST** : `smtp.gmail.com`
- **SMTP_PORT** : `587`
- **SMTP_SECURE** : `tls`
- **SMTP_PASS** : un **mot de passe d’application** (pas votre mot de passe Gmail habituel) :
  1. Allez sur https://myaccount.google.com/
  2. **Sécurité** → **Validation en 2 étapes** (activez-la si ce n’est pas déjà fait).
  3. **Mots de passe des applications** → Créez un mot de passe pour « Mail ».
  4. Copiez les 16 caractères dans **SMTP_PASS** dans `mail_smtp.php`.

### Avec Outlook / Microsoft 365

- **SMTP_HOST** : `smtp-mail.outlook.com` (ou `smtp.office365.com` pour Office 365)
- **SMTP_PORT** : `587`
- **SMTP_SECURE** : `tls`
- **SMTP_USER** / **SMTP_FROM_EMAIL** : votre adresse Outlook.
- **SMTP_PASS** : votre mot de passe Microsoft (ou mot de passe d’application si activé).

### Autres fournisseurs

Consultez la documentation de votre hébergeur de messagerie pour : serveur SMTP, port (souvent 587 ou 465) et type de sécurisation (TLS/SSL).

---

## 4. Exemple de contenu pour `config/mail_smtp.php`

```php
<?php
define('SMTP_FROM_EMAIL', 'monentreprise@gmail.com');
define('SMTP_FROM_NAME', 'Gestion RH');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USER', 'monentreprise@gmail.com');
define('SMTP_PASS', 'abcd efgh ijkl mnop');  // Mot de passe d'application Gmail
```

---

## 5. Sécurité

- **Ne commitez pas** le fichier **mail_smtp.php** dans Git (il contient votre mot de passe).
- Vous pouvez ajouter dans **.gitignore** :  
  `config/mail_smtp.php`

---

## 6. Vérification

Une fois la configuration en place, les e-mails déclenchés par l’application (par exemple notification de congé approuvé/refusé) seront envoyés **depuis votre adresse** vers les employés (adresse e-mail enregistrée dans la fiche employé).

Si aucun e-mail ne part, vérifiez que :
- `composer install` a bien créé le dossier **vendor**.
- **mail_smtp.php** existe et contient les bonnes valeurs (notamment **SMTP_PASS** pour Gmail).
