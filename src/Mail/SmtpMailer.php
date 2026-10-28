<?php

declare(strict_types=1);

namespace App\Mail;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;

final class SmtpMailer
{
    public function sendVerificationCode(string $email, string $fullName, string $code): void
    {
        $host = self::requiredEnvironmentValue('SMTP_HOST');
        $username = self::requiredEnvironmentValue('SMTP_USERNAME');
        $password = self::requiredEnvironmentValue('SMTP_PASSWORD');
        $fromEmail = self::requiredEnvironmentValue('SMTP_FROM_EMAIL');
        $port = filter_var(getenv('SMTP_PORT') ?: '587', FILTER_VALIDATE_INT);
        $encryption = strtolower(getenv('SMTP_ENCRYPTION') ?: 'tls');
        $fromName = getenv('SMTP_FROM_NAME') ?: 'Transporti';
        $appUrl = getenv('APP_URL') ?: '';

        if ($port === false || $port < 1 || $port > 65535) {
            throw new RuntimeException('SMTP_PORT duhet të jetë portë e vlefshme.');
        }
        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new RuntimeException('SMTP_FROM_EMAIL nuk është adresë e vlefshme.');
        }
        if (!in_array($encryption, ['tls', 'ssl'], true)) {
            throw new RuntimeException('SMTP_ENCRYPTION duhet të jetë tls ose ssl.');
        }
        if ($appUrl !== '') {
            $appUrlScheme = parse_url($appUrl, PHP_URL_SCHEME);
            if (
                filter_var($appUrl, FILTER_VALIDATE_URL) === false
                || !in_array(strtolower((string) $appUrlScheme), ['http', 'https'], true)
            ) {
                throw new RuntimeException('APP_URL duhet të jetë adresë e vlefshme HTTP ose HTTPS.');
            }
        }

        $mailer = new PHPMailer(true);
        try {
            $mailer->isSMTP();
            $mailer->Host = $host;
            $mailer->SMTPAuth = true;
            $mailer->Username = $username;
            $mailer->Password = $password;
            $mailer->Port = $port;
            $mailer->SMTPSecure = $encryption === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->CharSet = 'UTF-8';
            $mailer->setFrom($fromEmail, $fromName);
            $mailer->addAddress($email, $fullName);
            $mailer->isHTML(true);
            $mailer->Subject = 'Kodi i verifikimit të llogarisë';
            $escapedName = htmlspecialchars($fullName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $verificationUrl = $appUrl === '' ? '' : rtrim($appUrl, '/') . '/?page=verify-email';
            $verificationLink = $verificationUrl === ''
                ? '<p>Hap faqen “Verifiko email-in” në aplikacion për të futur kodin.</p>'
                : '<p><a href="' . htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                    . '">Hap faqen e verifikimit</a></p>';
            $mailer->Body = '<p>Përshëndetje ' . $escapedName . ',</p>'
                . '<p>Kodi yt i verifikimit është: <strong>' . $code . '</strong></p>'
                . '<p>Kodi skadon pas 10 minutash.</p>' . $verificationLink
                . '<p>Nëse nuk e ke kërkuar këtë llogari, injoroje këtë email.</p>';
            $mailer->AltBody = "Përshëndetje {$fullName}, kodi yt i verifikimit është {$code}. "
                . 'Kodi skadon pas 10 minutash.'
                . ($verificationUrl === '' ? '' : " Verifiko email-in: {$verificationUrl}");
            $mailer->send();
        } catch (Exception $exception) {
            throw new RuntimeException('Dërgimi i email-it përmes SMTP dështoi.', 0, $exception);
        }
    }

    private static function requiredEnvironmentValue(string $name): string
    {
        $value = getenv($name);
        if (!is_string($value) || trim($value) === '') {
            throw new RuntimeException("Konfigurimi {$name} mungon.");
        }
        return trim($value);
    }
}
