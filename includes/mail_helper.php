<?php
/**
 * Helper envoi d'e-mails - charge la config et expose sendMail()
 */
if (!function_exists('sendMail')) {
    require_once __DIR__ . '/../config/mail.php';
}
