<?php
/**
 * Session security: cookie params must be set before session_start().
 * HttpOnly and Secure (when HTTPS) reduce session hijacking risk.
 */
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
} else {
    session_set_cookie_params(0, '/', '', $isSecure, true);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security headers (send once, before any output)
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    // default-src 'self'; allow Bootstrap/BIcons from CDN (script, style, font)
    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; font-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:;");
    if ($isSecure) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
}

/** Session timeout (inactivity) in seconds — 15 minutes */
define('SESSION_TIMEOUT', 15 * 60);

/** CSRF token session key */
define('CSRF_TOKEN_KEY', 'csrf_token');

/** Brute-force protection: max failed attempts before lockout */
define('LOGIN_MAX_ATTEMPTS', 5);
/** Brute-force protection: lockout duration in seconds (15 minutes) */
define('LOGIN_LOCKOUT_DURATION', 15 * 60);

/**
 * Return a stable client identifier for login rate limiting (IP-based).
 * Uses REMOTE_ADDR; if behind a trusted proxy, you can add X-Forwarded-For with validation.
 */
function getLoginClientIdentifier() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return $ip;
}

/**
 * Check if this client is currently blocked due to too many failed logins.
 * Requires table: login_attempts (identifier, attempts, locked_until).
 * @param SafeDB $db
 * @param string $identifier From getLoginClientIdentifier()
 * @return bool True if blocked (should reject login)
 */
function isLoginBlocked($db, $identifier) {
    $row = $db->run(
        'SELECT locked_until FROM login_attempts WHERE identifier = ?',
        [$identifier]
    )->fetch();
    if (!$row || empty($row['locked_until'])) {
        return false;
    }
    return strtotime($row['locked_until']) > time();
}

/**
 * Return remaining lockout seconds (0 if not locked).
 * @param SafeDB $db
 * @param string $identifier
 * @return int
 */
function getLoginLockoutRemaining($db, $identifier) {
    $row = $db->run(
        'SELECT locked_until FROM login_attempts WHERE identifier = ?',
        [$identifier]
    )->fetch();
    if (!$row || empty($row['locked_until'])) {
        return 0;
    }
    $remaining = strtotime($row['locked_until']) - time();
    return $remaining > 0 ? (int) $remaining : 0;
}

/**
 * Record a failed login attempt. Call after each failed password check.
 * Increments attempts; if >= LOGIN_MAX_ATTEMPTS, sets locked_until for LOGIN_LOCKOUT_DURATION.
 * @param SafeDB $db
 * @param string $identifier
 * @param string|null $emailAttempted Email used in the attempt (for audit)
 */
function recordFailedLogin($db, $identifier, $emailAttempted = null) {
    $row = $db->run('SELECT attempts, locked_until FROM login_attempts WHERE identifier = ?', [$identifier])->fetch();
    $lockUntil = date('Y-m-d H:i:s', time() + LOGIN_LOCKOUT_DURATION);
    if (!$row) {
        $attempts = 1;
        $db->run(
            'INSERT INTO login_attempts (identifier, attempts, locked_until) VALUES (?, ?, ?)',
            [$identifier, $attempts, $attempts >= LOGIN_MAX_ATTEMPTS ? $lockUntil : null]
        );
    } else {
        $currentLocked = $row['locked_until'] && strtotime($row['locked_until']) > time();
        if ($currentLocked) {
            return;
        }
        // After lockout expired, start a new window (reset to 1); otherwise increment
        $lockedExpired = !empty($row['locked_until']) && strtotime($row['locked_until']) < time();
        $attempts = $lockedExpired ? 1 : (int) $row['attempts'] + 1;
        $db->run(
            'UPDATE login_attempts SET attempts = ?, locked_until = ? WHERE identifier = ?',
            [$attempts, $attempts >= LOGIN_MAX_ATTEMPTS ? $lockUntil : null, $identifier]
        );
    }
}

/**
 * Clear failed attempts for this client after a successful login.
 * @param SafeDB $db
 * @param string $identifier
 */
function clearLoginAttempts($db, $identifier) {
    $db->run('DELETE FROM login_attempts WHERE identifier = ?', [$identifier]);
}

/**
 * Log login-related events for audit (suspicious activity).
 * Requires table: login_audit (identifier, email_attempted, event, created_at).
 * @param SafeDB $db
 * @param string $identifier
 * @param string|null $emailAttempted
 * @param string $event 'failed' | 'blocked' | 'success'
 */
function logLoginAudit($db, $identifier, $emailAttempted, $event) {
    $db->run(
        'INSERT INTO login_audit (identifier, email_attempted, event) VALUES (?, ?, ?)',
        [$identifier, $emailAttempted ?? '', $event]
    );
    error_log(sprintf('[Gestion RH] Login %s: identifier=%s email=%s', $event, $identifier, $emailAttempted ?? ''));
}

/**
 * Enforce session timeout for authenticated or pre-auth (MFA) sessions.
 * Redirects to login with ?timeout=1 if inactive too long.
 */
function enforceSessionTimeout() {
    $hasSession = isset($_SESSION['user_id']) || isset($_SESSION['pending_user_id']);
    if (!$hasSession) {
        return;
    }
    $now = time();
    if (!empty($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', $now - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: /Gestion_RH/login.php?timeout=1');
        exit();
    }
    $_SESSION['last_activity'] = $now;
}

// Apply timeout on every request that loads auth
enforceSessionTimeout();

/**
 * Generate or return existing CSRF token (secure random, stored in session).
 * @return string Token value (64-char hex)
 */
function csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_KEY])) {
        $_SESSION[CSRF_TOKEN_KEY] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_KEY];
}

/**
 * Output hidden input for CSRF token. Include in every POST form.
 * @return string HTML <input type="hidden" ...>
 */
function csrf_field() {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate CSRF token from POST. Call before processing any form submission.
 * @return bool True if token is present and matches session
 */
function csrf_validate() {
    $submitted = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION[CSRF_TOKEN_KEY] ?? '';
    return $expected !== '' && hash_equals($expected, $submitted);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/** Admin et IT peuvent gérer les utilisateurs de l'application (rôles et mots de passe). */
function canManageUsers() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'it'], true);
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

/** Libellé du rôle pour l’affichage (topbar, sidebar droite). */
/**
 * Escape for HTML (XSS protection). Use for all user or DB output. UTF-8.
 */
function e($s) {
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function getRoleLabel($role) {
    $labels = [
        'dg' => 'Directeur général',
        'admin' => 'Administrateur',
        'rh' => 'Gestion des Ressources Humaines',
        'it' => 'Informatique'
    ];
    return $labels[$role] ?? 'Utilisateur';
}
?>
