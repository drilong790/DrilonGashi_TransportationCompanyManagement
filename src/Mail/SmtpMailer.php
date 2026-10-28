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
                ? ''
                : '<tr>
                    <td style="padding:0 32px 28px;text-align:center;">
                        <a href="' . htmlspecialchars($verificationUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                            . '" style="display:inline-block;padding:12px 22px;background:#2457d6;color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:600;">
                            Hap faqen e verifikimit
                        </a>
                    </td>
                </tr>';
            $mailer->Body = '<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body style="margin:0;padding:24px 12px;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#172033;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;background:#ffffff;border:1px solid #e4e9f2;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="padding:24px 32px;background:#173b8f;color:#ffffff;">
                            <div style="font-size:13px;letter-spacing:1px;text-transform:uppercase;">Transporti</div>
                            <div style="margin-top:5px;font-size:21px;font-weight:700;">Verifikimi i email-it</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px 12px;font-size:16px;line-height:1.6;">
                            Përshëndetje <strong>' . $escapedName . '</strong>,<br>
                            Fut kodin më poshtë për të konfirmuar adresën dhe aktivizuar llogarinë.
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:18px 32px;">
                            <div style="display:inline-block;padding:16px 28px;background:#f1f5ff;border:1px solid #dce6ff;border-radius:12px;color:#173b8f;font-size:32px;font-weight:700;letter-spacing:8px;">
                                ' . $code . '
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 32px 24px;text-align:center;color:#5d687a;font-size:14px;line-height:1.6;">
                            Kodi skadon pas <strong>10 minutash</strong>.<br>
                            Nëse nuk e ke kërkuar këtë llogari, mund ta injorosh këtë email.
                        </td>
                    </tr>
                    ' . $verificationLink . '
                    <tr>
                        <td style="padding:18px 32px;border-top:1px solid #edf0f5;color:#8791a1;font-size:12px;text-align:center;">
                            Mesazh automatik nga sistemi i menaxhimit të transportit.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
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
