<?php
require_once 'config/auth.php';
require_once 'config/database.php';

$error = '';
$success = '';

if (!isset($_SESSION['pending_user_id'])) {
    header('Location: login.php');
    exit();
}

if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    $mfaCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $_SESSION['mfa_code'] = $mfaCode;
    $_SESSION['mfa_expires'] = time() + 10 * 60;
    require_once 'config/mail.php';
    $sujet = 'Nouveau code de vérification - Gestion RH';
    $corps = '<p>Bonjour ' . htmlspecialchars($_SESSION['pending_nom_utilisateur'] ?? '') . ',</p><p>Votre nouveau code de vérification est : <strong>' . $mfaCode . '</strong></p><p>Ce code est valable 10 minutes.</p><p>Cordialement,<br>Gestion RH</p>';
    sendMail($_SESSION['pending_email'] ?? '', $sujet, $corps);
    $success = 'Un nouveau code a été envoyé à votre adresse e-mail.';
    header('Location: mfa_verify.php?sent=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        $error = 'Session expirée ou formulaire invalide. Veuillez réessayer.';
    } else {
    $code = preg_replace('/\D/', '', $_POST['code'] ?? '');

    if (strlen($code) !== 6) {
        $error = 'Le code doit contenir 6 chiffres.';
    } elseif (!isset($_SESSION['mfa_expires']) || time() > $_SESSION['mfa_expires']) {
        $error = 'Ce code a expiré. Veuillez vous reconnecter.';
        unset($_SESSION['pending_user_id'], $_SESSION['pending_nom_utilisateur'], $_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['mfa_code'], $_SESSION['mfa_expires']);
        header('Location: login.php?mfa_expired=1');
        exit();
    } elseif (!isset($_SESSION['mfa_code']) || $code !== $_SESSION['mfa_code']) {
        $error = 'Code incorrect.';
    } else {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $_SESSION['pending_user_id'];
        $_SESSION['nom_utilisateur'] = $_SESSION['pending_nom_utilisateur'];
        $_SESSION['email'] = $_SESSION['pending_email'];
        $_SESSION['role'] = $_SESSION['pending_role'];
        unset($_SESSION['pending_user_id'], $_SESSION['pending_nom_utilisateur'], $_SESSION['pending_email'], $_SESSION['pending_role'], $_SESSION['mfa_code'], $_SESSION['mfa_expires']);
        header('Location: index.php');
        exit();
    }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification en 2 étapes - Gestion RH</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body class="login-page">
    <div class="login-waves" aria-hidden="true">
        <div class="login-wave login-wave-1"></div>
        <div class="login-wave login-wave-2"></div>
        <div class="login-wave login-wave-3"></div>
    </div>

    <div class="login-box">
        <div class="login-avatar-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
        </div>
        <h1 class="login-title">VÉRIFICATION EN 2 ÉTAPES</h1>
        <p class="login-subtitle">Entrez le code envoyé à <?= htmlspecialchars($_SESSION['pending_email'] ?? '') ?></p>

        <?php if (isset($_GET['sent'])): ?>
            <div class="login-success" role="alert">Un nouveau code a été envoyé à votre adresse e-mail.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="login-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <?= csrf_field() ?>
            <input type="text" class="login-input" name="code" placeholder="Code à 6 chiffres" maxlength="6" pattern="[0-9]*" inputmode="numeric" required autofocus>
            <button type="submit" class="login-btn">Valider</button>
        </form>

        <p class="login-help">
            <a href="mfa_verify.php?resend=1" class="login-forgot">Renvoyer le code</a>
        </p>
        <a href="login.php" class="login-forgot" style="display:inline-block;margin-top:0.5rem;">Retour à la connexion</a>
    </div>
</body>
</html>
