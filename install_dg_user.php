<?php
/**
 * Script à exécuter une seule fois : crée un utilisateur avec le rôle DG.
 * Supprimez ce fichier après utilisation pour des raisons de sécurité.
 */
require_once 'config/database.php';

$email = 'dg@gestionrh.com';
$nom_utilisateur = 'dg';
$mot_de_passe_clair = 'Dg2026!';
$role = 'dg';

header('Content-Type: text/html; charset=utf-8');

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM utilisateurs WHERE LOWER(TRIM(email)) = ?");
    $stmt->execute([strtolower(trim($email))]);
    if ($stmt->fetch()) {
        echo '<h1>Utilisateur DG déjà existant</h1>';
        echo '<p>Un compte avec l’email <strong>' . htmlspecialchars($email) . '</strong> existe déjà.</p>';
        echo '<p>Si vous avez oublié le mot de passe, réinitialisez-le depuis la page <strong>Utilisateurs</strong> (connexion Admin) ou via la base de données.</p>';
        echo '<p><a href="login.php">Aller à la page de connexion</a></p>';
        exit;
    }

    $hash = password_hash($mot_de_passe_clair, PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO utilisateurs (nom_utilisateur, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nom_utilisateur, $email, $hash, $role]);

    echo '<h1>Utilisateur DG créé</h1>';
    echo '<div style="background:#e8f5e9; border:1px solid #4caf50; padding:1rem; margin:1rem 0; border-radius:8px;">';
    echo '<p><strong>Connectez-vous avec ces identifiants (rôle DG = lecture seule) :</strong></p>';
    echo '<ul style="list-style:none; padding:0;">';
    echo '<li><strong>Email :</strong> ' . htmlspecialchars($email) . '</li>';
    echo '<li><strong>Mot de passe :</strong> ' . htmlspecialchars($mot_de_passe_clair) . '</li>';
    echo '</ul>';
    echo '</div>';
    echo '<p><a href="login.php">Aller à la page de connexion</a></p>';
    echo '<p style="color:#d32f2f; margin-top:2rem;"><strong>Important :</strong> Supprimez ce fichier <code>install_dg_user.php</code> après connexion pour des raisons de sécurité.</p>';
} catch (PDOException $e) {
    echo '<h1>Erreur</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
