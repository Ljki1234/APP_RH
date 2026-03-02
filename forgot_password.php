<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$step = $_GET['step'] ?? 'request';
$error = '';
$success = '';

$db = getDB();

// Créer la table si elle n'existe pas
$db->exec("CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    code CHAR(6) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_code (email(100), code),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Étape 1 : demande de code (envoi par email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($step === 'request' || isset($_POST['action_request']))) {
    if (!csrf_validate()) {
        $error = 'Session expirée ou formulaire invalide. Veuillez réessayer.';
    } else {
    $email = trim($_POST['email'] ?? '');
    if ($email === '') {
        $error = 'Veuillez entrer votre adresse e-mail.';
    } else {
        $emailNorm = strtolower($email);
        $stmt = $db->prepare("SELECT id, email FROM utilisateurs WHERE LOWER(TRIM(email)) = ?");
        $stmt->execute([$emailNorm]);
        $user = $stmt->fetch();
        if (!$user) {
            $error = 'Aucun compte associé à cette adresse e-mail.';
        } else {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutes
            $stmt = $db->prepare("INSERT INTO password_reset_codes (email, code, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['email'], $code, $expiresAt]);

            require_once 'config/mail.php';
            $sujet = 'Code de réinitialisation - Gestion RH';
            $corps = '<p>Bonjour,</p><p>Votre code pour réinitialiser votre mot de passe est : <strong>' . $code . '</strong></p><p>Ce code est valable 15 minutes. Ne le partagez avec personne.</p><p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez cet e-mail.</p><p>Cordialement,<br>L\'équipe Gestion RH</p>';
            $emailSent = sendMail($user['email'], $sujet, $corps);
            if (!$emailSent) {
                $_SESSION['reset_code_fallback'] = $code;
            } else {
                unset($_SESSION['reset_code_fallback']);
            }

            $_SESSION['reset_email'] = $user['email'];
            header('Location: forgot_password.php?step=reset');
            exit();
        }
    }
    }
}

// Étape 2 : saisie du code et nouveau mot de passe
if ($step === 'reset') {
    $resetEmail = $_SESSION['reset_email'] ?? '';
    if ($resetEmail === '') {
        header('Location: forgot_password.php');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_reset'])) {
        if (!csrf_validate()) {
            $error = 'Session expirée ou formulaire invalide. Veuillez réessayer.';
        } else {
        $code = preg_replace('/\D/', '', $_POST['code'] ?? '');
        $nouveau = $_POST['nouveau_mot_de_passe'] ?? '';
        $confirme = $_POST['confirme_mot_de_passe'] ?? '';

        if (strlen($code) !== 6) {
            $error = 'Le code doit contenir 6 chiffres.';
        } elseif (strlen($nouveau) < 4) {
            $error = 'Le mot de passe doit contenir au moins 4 caractères.';
        } elseif ($nouveau !== $confirme) {
            $error = 'Les deux mots de passe ne correspondent pas.';
        } else {
            $stmt = $db->prepare("SELECT id FROM password_reset_codes WHERE LOWER(TRIM(email)) = ? AND code = ? AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([strtolower(trim($resetEmail)), $code]);
            $row = $stmt->fetch();
            if (!$row) {
                $error = 'Code incorrect ou expiré. Demandez un nouveau code.';
            } else {
                $hash = password_hash($nouveau, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE LOWER(TRIM(email)) = ?");
                $stmt->execute([$hash, strtolower(trim($resetEmail))]);
                $db->prepare("DELETE FROM password_reset_codes WHERE LOWER(TRIM(email)) = ?")->execute([strtolower(trim($resetEmail))]);
                unset($_SESSION['reset_email'], $_SESSION['reset_code_fallback']);
                header('Location: login.php?reset=1');
                exit();
            }
        }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Gestion RH</title>
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
        <h1 class="login-title"><?= $step === 'reset' ? 'NOUVEAU MOT DE PASSE' : 'MOT DE PASSE OUBLIÉ' ?></h1>
        <p class="login-subtitle"><?= $step === 'reset' ? 'Entrez le code reçu par e-mail' : 'Recevez un code à 6 chiffres par e-mail' ?></p>

        <?php if ($error): ?>
            <div class="login-error" role="alert"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($step === 'reset' && !empty($_SESSION['reset_code_fallback'])): ?>
            <div class="login-fallback" role="alert">
                L'e-mail n'a pas pu être envoyé (vérifiez la configuration SMTP dans <strong>config/mail_smtp.php</strong>).<br>
                <?php if (!empty($_SESSION['mail_last_error'])): ?>
                    <em>Détail : <?= htmlspecialchars($_SESSION['mail_last_error']) ?></em><br>
                    <?php unset($_SESSION['mail_last_error']); ?>
                <?php endif; ?>
                <strong>Utilisez ce code pour réinitialiser : <?= htmlspecialchars($_SESSION['reset_code_fallback']) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($step === 'reset'): ?>
            <form method="POST" action="" class="login-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action_reset" value="1">
                <input type="text" class="login-input" name="code" placeholder="Code à 6 chiffres" maxlength="6" pattern="[0-9]*" inputmode="numeric" required autofocus>
                <input type="password" class="login-input" name="nouveau_mot_de_passe" placeholder="Nouveau mot de passe" minlength="4" required>
                <input type="password" class="login-input" name="confirme_mot_de_passe" placeholder="Confirmer le mot de passe" minlength="4" required>
                <button type="submit" class="login-btn">Changer le mot de passe</button>
            </form>
            <p class="login-help">Code envoyé à <?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?></p>
            <a href="forgot_password.php" class="login-forgot" style="display:inline-block;margin-top:0.5rem;">Demander un nouveau code</a>
        <?php else: ?>
            <form method="POST" action="" class="login-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action_request" value="1">
                <input type="email" class="login-input" name="email" placeholder="Votre adresse e-mail" required autofocus>
                <button type="submit" class="login-btn">Envoyer le code</button>
            </form>
        <?php endif; ?>

        <a href="login.php" class="login-forgot" style="display:inline-block;margin-top:1rem;">Retour à la connexion</a>
    </div>
</body>
</html>
