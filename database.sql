
CREATE DATABASE IF NOT EXISTS gestion_rh CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE gestion_rh;


CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_utilisateur VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'rh', 'it', 'dg') DEFAULT 'rh',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code CHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_code (email(100), code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS departements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS employes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    matricule VARCHAR(20) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    date_naissance DATE,
    date_embauche DATE NOT NULL,
    poste VARCHAR(100) NOT NULL,
    departement_id INT,
    salaire_base DECIMAL(10, 2) DEFAULT 0.00,
    statut ENUM('actif', 'inactif', 'congé', 'démission') DEFAULT 'actif',
    photo VARCHAR(255),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (departement_id) REFERENCES departements(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS conges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employe_id INT NOT NULL,
    type_conge ENUM('annuel', 'maladie', 'maternité', 'paternité', 'exceptionnel', 'sans_solde') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    nombre_jours INT NOT NULL,
    statut ENUM('en_attente', 'approuvé', 'refusé') DEFAULT 'en_attente',
    motif TEXT,
    date_demande DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_traitement DATETIME NULL,
    traite_par INT NULL,
    FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE CASCADE,
    FOREIGN KEY (traite_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS salaires (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employe_id INT NOT NULL,
    mois INT NOT NULL,
    annee INT NOT NULL,
    salaire_base DECIMAL(10, 2) NOT NULL,
    prime DECIMAL(10, 2) DEFAULT 0.00,
    heures_supplementaires DECIMAL(5, 2) DEFAULT 0.00,
    montant_heures_sup DECIMAL(10, 2) DEFAULT 0.00,
    retenues DECIMAL(10, 2) DEFAULT 0.00,
    salaire_net DECIMAL(10, 2) NOT NULL,
    date_paiement DATE,
    statut ENUM('en_attente', 'payé', 'annulé') DEFAULT 'en_attente',
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_salaire (employe_id, mois, annee)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS presences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employe_id INT NOT NULL,
    date_presence DATE NOT NULL,
    heure_arrivee TIME,
    heure_depart TIME,
    heures_travaillees DECIMAL(5, 2) DEFAULT 0.00,
    statut ENUM('présent', 'absent', 'retard', 'congé') DEFAULT 'présent',
    remarques TEXT,
    FOREIGN KEY (employe_id) REFERENCES employes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_presence (employe_id, date_presence)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO utilisateurs (nom_utilisateur, email, mot_de_passe, role) VALUES
('admin', 'admin@gestionrh.com', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', 'admin');


INSERT INTO departements (nom, description) VALUES
('Direction Générale', 'Direction générale de l\'entreprise'),
('Ressources Humaines', 'Gestion du personnel et des ressources humaines'),
('Informatique', 'Développement et maintenance informatique'),
('Comptabilité', 'Gestion financière et comptable'),
('Commercial', 'Vente et relation client'),
('Production', 'Fabrication et production');
