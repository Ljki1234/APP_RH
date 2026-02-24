-- Ajouter les rôles 'it' et 'dg' pour les utilisateurs (exécuter si la base existe déjà)
USE gestion_rh;
ALTER TABLE utilisateurs MODIFY COLUMN role ENUM('admin', 'rh', 'it', 'dg') DEFAULT 'rh';
