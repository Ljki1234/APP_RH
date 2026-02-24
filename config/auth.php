<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Accès total (RH, IT, admin) : peut ajouter, modifier, supprimer.
 * DG = accès limité (lecture seule).
 */
function hasFullAccess() {
    if (!isset($_SESSION['role'])) return false;
    return in_array($_SESSION['role'], ['admin', 'rh', 'it'], true);
}

function requireLogin() {
    if (isset($_SESSION['pending_user_id']) && !isset($_SESSION['user_id'])) {
        header('Location: /Gestion_RH/mfa_verify.php');
        exit();
    }
    if (!isLoggedIn()) {
        header('Location: /Gestion_RH/login.php');
        exit();
    }
}


function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'nom_utilisateur' => $_SESSION['nom_utilisateur'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}
?>
