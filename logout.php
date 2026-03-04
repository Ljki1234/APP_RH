<?php
require_once 'config/auth.php';

// Audit: user-initiated logout (if logged in)
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    $db = getDB();
    logActivity($db, 'LOGOUT', null, null, null, null);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: /Gestion_RH/login.php');
exit();
