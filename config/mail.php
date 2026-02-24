<?php
/**
 * Configuration envoi d'e-mails
 *
 * Pour envoyer les mails avec VOTRE adresse (Gmail, Outlook, etc.) :
 * 1. Installez PHPMailer : ouvrez un terminal dans le dossier Gestion_RH et tapez :
 *       composer install
 * 2. Copiez config/mail_smtp.example.php vers config/mail_smtp.php
 * 3. Ouvrez config/mail_smtp.php et mettez votre adresse e-mail et mot de passe d'application
 * 4. Les e-mails (ex. notification congé approuvé/refusé) partiront depuis votre adresse
 *
 * Sans config SMTP, la fonction mail() PHP est utilisée (souvent ne fonctionne pas sur XAMPP).
 */

if (is_file(__DIR__ . '/mail_smtp.php')) {
    require_once __DIR__ . '/mail_smtp.php';
}
if (!defined('MAIL_FROM_EMAIL')) {
    define('MAIL_FROM_EMAIL', defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@gestionrh.com');
}
if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Gestion RH');
}

/**
 * Envoie un e-mail (HTML). Utilise SMTP si configuré (mail_smtp.php + PHPMailer), sinon mail().
 *
 * @param string $to       Adresse du destinataire (ex. client / employé)
 * @param string $subject  Sujet du message
 * @param string $bodyHtml Corps du message en HTML
 * @param string|null $bodyText Version texte (optionnel)
 * @return bool True si l'envoi a été accepté, false sinon
 */
function sendMail($to, $subject, $bodyHtml, $bodyText = null) {
    $to = trim($to);
    if ($to === '') return false;

    $fromEmail = MAIL_FROM_EMAIL;
    $fromName  = MAIL_FROM_NAME;
    $bodyHtml  = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body style="font-family:sans-serif;line-height:1.5;">' . $bodyHtml . '</body></html>';

    $useSmtp = defined('SMTP_HOST') && SMTP_HOST !== '' && defined('SMTP_USER') && is_file(__DIR__ . '/../vendor/autoload.php');

    if ($useSmtp) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->CharSet   = 'UTF-8';
            $mail->Encoding  = 'base64';
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
            $mail->Port       = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;

            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->msgHTML($bodyHtml);
            if ($bodyText !== null && $bodyText !== '') {
                $mail->AltBody = $bodyText;
            }

            return $mail->send();
        } catch (Exception $e) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['mail_last_error'] = $e->getMessage();
            return false;
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . mb_encode_mimeheader($fromName, 'UTF-8', 'Q') . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . phpversion(),
    ];
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $bodyHtml, implode("\r\n", $headers));
}
