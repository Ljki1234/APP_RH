# Application de Gestion RH

Application complète de gestion des ressources humaines développée en PHP avec MySQL. Cette application permet de gérer efficacement les ressources humaines d'une entreprise avec une interface moderne et intuitive.

##  Résumé des Fonctionnalités

###  Authentification et Sécurité
- Système de connexion sécurisé avec gestion des rôles (Admin, RH)
- Sessions sécurisées avec protection contre les attaques
- Hashage des mots de passe avec l'algorithme bcrypt
- Protection contre les injections SQL et les attaques XSS

###  Gestion des Employés
- **Ajout d'employés** : Enregistrement complet avec matricule, coordonnées, poste, département
- **Modification** : Mise à jour des informations personnelles et professionnelles
- **Suppression** : Gestion du statut (actif, inactif, congé, démission)
- **Recherche** : Recherche rapide par nom, matricule ou email
- **Affichage** : Liste complète avec filtres et tri

###  Gestion des Départements
- Création et modification des départements
- Attribution des employés aux départements
- Comptage automatique du nombre d'employés par département
- Description détaillée de chaque département

###  Gestion des Congés
- **Demande de congés** : Formulaire de demande avec différents types (annuel, maladie, maternité, etc.)
- **Approbation/Refus** : Traitement des demandes par les responsables RH
- **Suivi** : Statut en temps réel (en attente, approuvé, refusé)
- **Calcul automatique** : Nombre de jours de congé calculé automatiquement
- **Filtres** : Affichage par statut (tous, en attente, approuvés, refusés)

###  Gestion des Salaires
- **Calcul automatique** : Salaire net calculé automatiquement (base + prime + heures sup - retenues)
- **Suivi mensuel** : Gestion des salaires par mois et année
- **Détails** : Primes, heures supplémentaires, retenues
- **Statut de paiement** : Suivi (en attente, payé, annulé)
- **Totalisation** : Affichage du total des salaires pour une période donnée

###  Gestion des Présences
- **Enregistrement** : Heure d'arrivée et de départ
- **Calcul automatique** : Heures travaillées calculées automatiquement
- **Statuts** : Présent, absent, retard, congé
- **Suivi quotidien** : Affichage par date avec filtres
- **Remarques** : Notes additionnelles pour chaque présence

###  Tableau de Bord
- **Statistiques en temps réel** :
  - Nombre d'employés actifs
  - Nombre de départements
  - Congés en attente de traitement
  - Salaires du mois en cours
- **Vue d'ensemble** : Derniers employés ajoutés et congés récents
- **Navigation rapide** : Accès direct aux différentes sections

## Fonctionnalités Détaillées

-  **Authentification** : Système de connexion sécurisé avec gestion des rôles
-  **Gestion des employés** : CRUD complet (Créer, Lire, Modifier, Supprimer)
-  **Gestion des départements** : Organisation par départements
-  **Gestion des congés** : Demande, approbation et suivi des congés
-  **Gestion des salaires** : Calcul et suivi des salaires mensuels
-  **Gestion des présences** : Enregistrement et suivi des présences
-  **Tableau de bord** : Vue d'ensemble avec statistiques

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur (ou MariaDB)
- Serveur web (Apache avec XAMPP recommandé)
- Extensions PHP : PDO, PDO_MySQL

##  Guide d'Installation et d'Exécution - Étape par Étape

### Étape 1 : Vérifier les Prérequis

Avant de commencer, assurez-vous d'avoir installé :
-  **XAMPP** (ou WAMP/MAMP) avec PHP 7.4+
-  **MySQL** activé dans XAMPP
-  **Apache** activé dans XAMPP

### Étape 2 : Démarrer les Services XAMPP

1. **Ouvrez le Panneau de Contrôle XAMPP**
   - Localisez l'icône XAMPP dans votre menu Démarrer
   - Ou ouvrez `C:\xampp\xampp-control.exe`

2. **Démarrez Apache**
   - Cliquez sur le bouton **"Start"** à côté d'Apache
   - Attendez que le statut passe au vert 
   - Si le port 80 est occupé, modifiez-le dans les paramètres

3. **Démarrez MySQL**
   - Cliquez sur le bouton **"Start"** à côté de MySQL
   - Attendez que le statut passe au vert 
   - Vérifiez qu'il n'y a pas d'erreurs dans les logs

### Étape 3 : Vérifier que le Projet est au Bon Endroit

1. **Vérifiez l'emplacement du projet**
   - Le dossier `Gestion_RH` doit être dans : `C:\xampp\htdocs\Gestion_RH`
   - Si ce n'est pas le cas, déplacez le dossier à cet emplacement

2. **Vérifiez la structure des fichiers**
   - Ouvrez le dossier et vérifiez que tous les fichiers sont présents :
     - `config/` (avec `database.php` et `auth.php`)
     - `includes/` (avec `header.php` et `footer.php`)
     - `assets/` (avec `css/style.css` et `js/main.js`)
     - `database.sql`
     - `login.php`, `index.php`, etc.

### Étape 4 : Créer la Base de Données

**Option A : Via phpMyAdmin (Recommandé)**

1. **Ouvrez phpMyAdmin**
   - Dans votre navigateur, allez sur : `http://localhost/phpmyadmin`
   - Vous devriez voir l'interface phpMyAdmin

2. **Importez le fichier SQL**
   - Cliquez sur l'onglet **"Importer"** (ou "Import") en haut
   - Cliquez sur **"Choisir un fichier"** ou **"Browse"**
   - Sélectionnez le fichier `database.sql` dans le dossier `C:\xampp\htdocs\Gestion_RH\`
   - Cliquez sur **"Exécuter"** ou **"Go"** en bas de la page
   - Attendez le message de succès 

3. **Vérifiez la création**
   - Dans le menu de gauche, vous devriez voir la base `gestion_rh`
   - Cliquez dessus et vérifiez que toutes les tables sont présentes :
     - `utilisateurs`
     - `departements`
     - `employes`
     - `conges`
     - `salaires`
     - `presences`

**Option B : Via Ligne de Commande**

1. **Ouvrez PowerShell ou Invite de Commande**
2. **Naviguez vers le dossier du projet** :
   ```powershell
   cd C:\xampp\htdocs\Gestion_RH
   ```
3. **Importez la base de données** :
   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root gestion_rh < database.sql
   ```
   Ou avec PowerShell :
   ```powershell
   Get-Content database.sql | C:\xampp\mysql\bin\mysql.exe -u root gestion_rh
   ```

### Étape 5 : Configurer la Connexion à la Base de Données

1. **Ouvrez le fichier de configuration**
   - Ouvrez : `C:\xampp\htdocs\Gestion_RH\config\database.php`

2. **Vérifiez les paramètres** (généralement corrects par défaut) :
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'gestion_rh');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

3. **Modifiez si nécessaire**
   - Si votre MySQL utilise un autre utilisateur ou mot de passe
   - Si votre MySQL est sur un autre port (par défaut : 3306)

### Étape 6 : Corriger le Mot de Passe Admin (Important !)

 **Cette étape est essentielle pour pouvoir se connecter !**

1. **Ouvrez votre navigateur**
2. **Allez sur** : `http://localhost/Gestion_RH/update_admin_password.php`
3. **Attendez le message de succès** 
4. Le mot de passe admin sera automatiquement corrigé

**Alternative** : Si le script ne fonctionne pas, exécutez cette requête SQL dans phpMyAdmin :
```sql
UPDATE utilisateurs 
SET mot_de_passe = '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW' 
WHERE nom_utilisateur = 'admin';
```

### Étape 7 : Accéder à l'Application

1. **Ouvrez votre navigateur web** (Chrome, Firefox, Edge, etc.)

2. **Allez sur l'URL de connexion** :
   ```
   http://localhost/Gestion_RH/login.php
   ```

3. **Connectez-vous avec les identifiants par défaut** :
   - **Nom d'utilisateur** : `admin`
   - **Mot de passe** : `admin123`

4. **Vous devriez être redirigé vers le tableau de bord** 

### Étape 8 : Vérifier que Tout Fonctionne

1. **Testez la connexion** :
   - Allez sur : `http://localhost/Gestion_RH/test_connection.php`
   - Vérifiez que toutes les tables sont présentes 

2. **Explorez l'application** :
   - Tableau de bord : Statistiques et vue d'ensemble
   - Employés : Ajoutez un employé de test
   - Départements : Créez un département de test
   - Congés : Testez une demande de congé
   - Salaires : Ajoutez un salaire de test
   - Présences : Enregistrez une présence

### Étape 9 : Première Utilisation

1. **Changez le mot de passe admin** (Recommandé)
   - Après la première connexion, créez un script pour changer le mot de passe
   - Ou modifiez-le directement dans la base de données

2. **Créez vos départements**
   - Allez dans "Départements"
   - Ajoutez les départements de votre entreprise

3. **Ajoutez vos employés**
   - Allez dans "Employés"
   - Remplissez les informations de chaque employé

4. **Configurez les salaires**
   - Définissez les salaires de base pour chaque employé
   - Les salaires mensuels seront calculés automatiquement

##  Compte Administrateur par Défaut

- **Nom d'utilisateur** : `admin`
- **Mot de passe** : `admin123`
- **Email** : `admin@gestionrh.com`
- **Rôle** : `admin`

⚠️ **Important** : Changez le mot de passe après la première connexion pour des raisons de sécurité !

## Structure du projet

```
Gestion_RH/
├── config/
│   ├── database.php      # Configuration de la base de données
│   └── auth.php          # Fonctions d'authentification
├── includes/
│   ├── header.php        # En-tête commun
│   └── footer.php         # Pied de page commun
├── assets/
│   ├── css/
│   │   └── style.css     # Styles personnalisés
│   └── js/
│       └── main.js        # Scripts JavaScript
├── index.php              # Tableau de bord
├── login.php              # Page de connexion
├── logout.php             # Déconnexion
├── employes.php           # Gestion des employés
├── departements.php       # Gestion des départements
├── conges.php             # Gestion des congés
├── salaires.php           # Gestion des salaires
├── presences.php          # Gestion des présences
├── database.sql           # Script SQL de création
└── README.md              # Ce fichier
```

##  Guide d'Utilisation Détaillé

###  Gestion des Employés

**Ajouter un employé** :
1. Cliquez sur **"Employés"** dans le menu de navigation
2. Cliquez sur le bouton **"Ajouter un employé"** en haut à droite
3. Remplissez le formulaire :
   - **Matricule** : Identifiant unique (obligatoire)
   - **Nom et Prénom** : Informations personnelles
   - **Email** : Adresse email (obligatoire, unique)
   - **Téléphone** : Numéro de contact
   - **Adresse** : Adresse complète
   - **Date de naissance** : Date de naissance
   - **Date d'embauche** : Date d'entrée dans l'entreprise (obligatoire)
   - **Poste** : Fonction occupée (obligatoire)
   - **Département** : Sélectionnez le département
   - **Salaire de base** : Salaire mensuel de base
   - **Statut** : Actif, Inactif, Congé, Démission
4. Cliquez sur **"Enregistrer"**

**Modifier un employé** :
1. Dans la liste des employés, cliquez sur l'icône **  Modifier**
2. Modifiez les informations souhaitées
3. Cliquez sur **"Enregistrer"**

**Supprimer un employé** :
1. Cliquez sur l'icône ** Supprimer** à côté de l'employé
2. Confirmez la suppression

**Rechercher un employé** :
1. Utilisez la barre de recherche en haut de la liste
2. Recherchez par nom, prénom, matricule ou email
3. Cliquez sur **"Effacer"** pour réinitialiser

###  Gestion des Départements

**Ajouter un département** :
1. Cliquez sur **"Départements"** dans le menu
2. Cliquez sur **"Ajouter un département"**
3. Remplissez :
   - **Nom** : Nom du département (obligatoire)
   - **Description** : Description détaillée
4. Cliquez sur **"Enregistrer"**

**Modifier/Supprimer** : Utilisez les boutons d'action dans la liste

###  Gestion des Congés

**Demander un congé** :
1. Allez dans **"Congés"**
2. Cliquez sur **"Demander un congé"**
3. Remplissez le formulaire :
   - **Employé** : Sélectionnez l'employé concerné
   - **Type de congé** : Annuel, Maladie, Maternité, Paternité, Exceptionnel, Sans solde
   - **Date de début** : Date de début du congé
   - **Date de fin** : Date de fin du congé
   - **Motif** : Raison du congé (optionnel)
4. Le nombre de jours est calculé automatiquement
5. Cliquez sur **"Enregistrer"**

**Approuver/Refuser un congé** :
1. Dans la liste des congés, trouvez le congé en attente
2. Cliquez sur ** Approuver** ou ** Refuser**
3. Confirmez l'action

**Filtrer les congés** :
- Utilisez les boutons de filtre : Tous, En attente, Approuvés, Refusés

###  Gestion des Salaires

**Ajouter un salaire** :
1. Accédez à **"Salaires"**
2. Cliquez sur **"Ajouter un salaire"**
3. Remplissez les informations :
   - **Employé** : Sélectionnez l'employé (le salaire de base sera automatiquement rempli)
   - **Mois et Année** : Période concernée
   - **Salaire de base** : Rempli automatiquement, modifiable
   - **Prime** : Prime éventuelle
   - **Heures supplémentaires** : Nombre d'heures
   - **Montant heures sup** : Montant des heures supplémentaires
   - **Retenues** : Retenues diverses (impôts, etc.)
   - **Salaire net** : Calculé automatiquement
   - **Date de paiement** : Date de versement
   - **Statut** : En attente, Payé, Annulé
4. Cliquez sur **"Enregistrer"**

**Filtrer par période** :
1. Utilisez les filtres en haut de la page
2. Sélectionnez le mois et l'année
3. Cliquez sur **"Filtrer"**
4. Le total des salaires nets s'affiche automatiquement

###  Gestion des Présences

**Enregistrer une présence** :
1. Allez dans **"Présences"**
2. Cliquez sur **"Enregistrer une présence"**
3. Remplissez :
   - **Employé** : Sélectionnez l'employé
   - **Date** : Date de la présence
   - **Statut** : Présent, Absent, Retard, Congé
   - **Heure d'arrivée** : Heure d'arrivée (optionnel)
   - **Heure de départ** : Heure de départ (optionnel)
   - **Remarques** : Notes additionnelles
4. Les heures travaillées sont calculées automatiquement
5. Cliquez sur **"Enregistrer"**

**Consulter les présences** :
1. Utilisez le filtre de date en haut
2. Sélectionnez une date pour voir toutes les présences du jour
3. Les heures travaillées s'affichent automatiquement

###  Tableau de Bord

Le tableau de bord affiche :
- **Statistiques en temps réel** :
  - Nombre d'employés actifs
  - Nombre de départements
  - Congés en attente
  - Salaires du mois en cours
- **Derniers employés** : Les 5 derniers employés ajoutés
- **Congés récents** : Les 5 derniers congés demandés

**Navigation** :
- Cliquez sur les cartes de statistiques pour accéder rapidement aux sections
- Utilisez le menu de navigation en haut pour accéder à toutes les fonctionnalités

## Sécurité

- Les mots de passe sont hashés avec `password_hash()` PHP
- Protection contre les injections SQL avec les requêtes préparées PDO
- Protection XSS avec `htmlspecialchars()`
- Sessions sécurisées pour l'authentification

## Personnalisation

### Modifier le thème

Les couleurs et styles peuvent être modifiés dans `assets/css/style.css`

### Ajouter des fonctionnalités

L'application est modulaire et facilement extensible. Vous pouvez ajouter :
- Des rapports personnalisés
- Des exports Excel/PDF
- Des notifications par email
- Un système de permissions plus avancé

##  Dépannage (Résolution des Problèmes)

### Problème : "Erreur de connexion à la base de données"

**Solutions** :
1.  Vérifiez que MySQL est démarré dans XAMPP (statut vert)
2.  Vérifiez que la base de données `gestion_rh` existe dans phpMyAdmin
3.  Vérifiez les paramètres dans `config/database.php`
4.  Testez la connexion avec : `http://localhost/Gestion_RH/test_connection.php`

### Problème : "Nom d'utilisateur ou mot de passe incorrect"

**Solutions** :
1.  Exécutez le script de correction : `http://localhost/Gestion_RH/update_admin_password.php`
2.  Vérifiez que l'utilisateur `admin` existe dans la table `utilisateurs`
3.  Réimportez le fichier `database.sql` si nécessaire

### Problème : "Page blanche" ou "Erreur 500"

**Solutions** :
1.  Vérifiez les logs d'erreur PHP dans `C:\xampp\php\logs\php_error_log`
2.  Activez l'affichage des erreurs dans `php.ini` :
   ```ini
   display_errors = On
   error_reporting = E_ALL
   ```
3.  Vérifiez que toutes les extensions PHP sont activées (PDO, PDO_MySQL)
4.  Vérifiez les permissions des fichiers

### Problème : "Apache ne démarre pas"

**Solutions** :
1.  Vérifiez qu'aucun autre serveur web n'utilise le port 80
2.  Changez le port Apache dans les paramètres XAMPP (ex: 8080)
3.  Redémarrez XAMPP en tant qu'administrateur
4.  Vérifiez les logs Apache dans `C:\xampp\apache\logs\error.log`

### Problème : "MySQL ne démarre pas"

**Solutions** :
1.  Vérifiez qu'aucun autre MySQL n'est en cours d'exécution
2.  Changez le port MySQL dans les paramètres XAMPP (ex: 3307)
3.  Redémarrez XAMPP en tant qu'administrateur
4.  Vérifiez les logs MySQL dans `C:\xampp\mysql\data\mysql_error.log`

### Problème : "Les fichiers CSS/JS ne se chargent pas"

**Solutions** :
1.  Vérifiez que le dossier `assets` existe et contient les fichiers
2.  Vérifiez les chemins dans les fichiers PHP (doivent être relatifs)
3.  Videz le cache de votre navigateur (Ctrl + F5)
4.  Vérifiez la console du navigateur (F12) pour les erreurs

##  Support et Aide

Pour toute question ou problème, vérifiez dans cet ordre :

1.  **Vérifiez les prérequis** : XAMPP installé, services démarrés
2.  **Vérifiez la base de données** : Tables créées, utilisateur admin présent
3.  **Vérifiez les logs** : 
   - Logs PHP : `C:\xampp\php\logs\php_error_log`
   - Logs Apache : `C:\xampp\apache\logs\error.log`
   - Logs MySQL : `C:\xampp\mysql\data\mysql_error.log`
4.  **Testez la connexion** : Utilisez `test_connection.php`
5.  **Vérifiez les extensions PHP** : PDO et PDO_MySQL doivent être activées
6.  **Vérifiez les permissions** : Les fichiers doivent être lisibles par Apache

## Licence

Ce projet est fourni tel quel pour usage éducatif et professionnel.

## Auteur

Application développée pour la gestion des ressources humaines.

---

**Version** : 1.0.0  
**Dernière mise à jour** : 2026
