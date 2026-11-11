<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Authentication;
use PDO;

final class AuthController
{
    public static function handle(PDO $pdo, string $pageKey, array $navigationItems): array
    {
        $state = [
            'user' => null,
            'loginError' => null,
            'registerError' => null,
            'registerValues' => ['full_name' => '', 'email' => ''],
            'verificationEmail' => '',
            'verificationError' => null,
            'verificationMessage' => null,
        ];

        $state['user'] = Authentication::currentUser($pdo);
        if ($state['user'] !== null && $pageKey !== 'verify-email') {
            header('Location: ?page=dashboard');
            exit;
        }

        $state['verificationEmail'] = is_string($_SESSION['verification_email'] ?? null)
            ? $_SESSION['verification_email']
            : '';
        $state['verificationMessage'] = $_SESSION['verification_message'] ?? null;
        unset($_SESSION['verification_message']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($pageKey === 'register') {
                self::register($pdo, $state);
            } elseif ($pageKey === 'verify-email') {
                self::verifyEmail($pdo, $state);
            } elseif ($pageKey === 'login') {
                self::login($pdo, $state, $navigationItems);
            }
        }

        return $state;
    }

    private static function register(PDO $pdo, array &$state): void
    {
        $fullName = $_POST['full_name'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $passwordConfirmation = $_POST['password_confirmation'] ?? null;
        if (is_string($fullName)) {
            $state['registerValues']['full_name'] = trim($fullName);
        }
        if (is_string($email)) {
            $state['registerValues']['email'] = trim($email);
        }

        if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $state['registerError'] = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
        } elseif (!is_string($fullName) || trim($fullName) === '' || mb_strlen(trim($fullName)) > 150) {
            http_response_code(422);
            $state['registerError'] = 'Emri është i detyrueshëm dhe duhet të ketë maksimumi 150 karaktere.';
        } elseif (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false || mb_strlen(trim($email)) > 150) {
            http_response_code(422);
            $state['registerError'] = 'Shkruaj një adresë email të vlefshme (maksimumi 150 karaktere).';
        } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
            http_response_code(422);
            $state['registerError'] = 'Fjalëkalimi duhet të ketë të paktën 12 karaktere dhe maksimumi 72 bajte.';
        } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
            http_response_code(422);
            $state['registerError'] = 'Fjalëkalimet nuk përputhen.';
        } else {
            $normalizedEmail = mb_strtolower(trim($email));
            $userId = Authentication::register($pdo, trim($fullName), $normalizedEmail, $password);
            if ($userId === null) {
                http_response_code(409);
                $state['registerError'] = 'Nuk mund të krijohet llogaria me këtë email. Kontrollo të dhënat ose provo të hysh.';
                return;
            }

            $_SESSION['verification_email'] = $normalizedEmail;
            try {
                Authentication::sendVerificationCode($pdo, $userId, $normalizedEmail, trim($fullName));
                $_SESSION['verification_message'] = 'Llogaria u krijua. Kodi i verifikimit u dërgua në email-in tënd.';
            } catch (\RuntimeException $exception) {
                $_SESSION['verification_message'] = 'Llogaria u krijua, por kodi nuk u dërgua. Kontrollo konfigurimin SMTP ose provo ridërgimin.';
            }
            header('Location: ?page=verify-email');
            exit;
        }
    }

    private static function verifyEmail(PDO $pdo, array &$state): void
    {
        $email = $_POST['email'] ?? null;
        $action = $_POST['action'] ?? null;
        $state['verificationEmail'] = is_string($email) ? mb_strtolower(trim($email)) : '';
        if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $state['verificationError'] = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
        } elseif (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
            http_response_code(422);
            $state['verificationError'] = 'Shkruaj një adresë email të vlefshme.';
        } elseif ($action === 'verify') {
            $code = $_POST['code'] ?? null;
            if (!is_string($code) || !Authentication::verifyEmail($pdo, $state['verificationEmail'], trim($code))) {
                http_response_code(422);
                $state['verificationError'] = 'Kodi nuk është i vlefshëm, ka skaduar ose është tejkaluar numri i tentativave.';
            } else {
                unset($_SESSION['verification_email']);
                $_SESSION['flash_message'] = 'Email-i u verifikua. Tani mund të hysh në llogari.';
                header('Location: ?page=login');
                exit;
            }
        } elseif ($action === 'resend') {
            try {
                $sent = Authentication::resendVerificationCode($pdo, $state['verificationEmail']);
                $state['verificationMessage'] = $sent
                    ? 'Nëse ekziston një llogari e paverifikuar me këtë email, kodi u dërgua. Prit të paktën 60 sekonda para një kërkese tjetër.'
                    : 'Prit të paktën 60 sekonda para se të kërkosh një kod tjetër.';
            } catch (\RuntimeException $exception) {
                $state['verificationError'] = 'Kodi nuk mund të dërgohej. Kontrollo konfigurimin SMTP dhe provo përsëri pas pak.';
            }
        } else {
            http_response_code(400);
            $state['verificationError'] = 'Veprimi i kërkuar nuk është i vlefshëm.';
        }

        $_SESSION['verification_email'] = $state['verificationEmail'];
    }

    private static function login(PDO $pdo, array &$state, array $navigationItems): void
    {
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $state['loginError'] = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
        } elseif (!is_string($email) || !is_string($password) || trim($email) === '' || $password === '') {
            http_response_code(422);
            $state['loginError'] = 'Shkruaj email-in dhe fjalëkalimin.';
        } else {
            $user = Authentication::attempt($pdo, trim($email), $password);
            if ($user === null) {
                http_response_code(401);
                $state['loginError'] = 'Email-i ose fjalëkalimi nuk është i saktë, ose llogaria është çaktivizuar.';
                return;
            }

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $destination = $_SESSION['redirect_after_login'] ?? 'dashboard';
            unset($_SESSION['redirect_after_login']);
            $allowedDestinations = array_merge(
                array_keys($navigationItems),
                ['add-admin', 'users', 'finances', 'not_found']
            );
            if (!is_string($destination) || !in_array($destination, $allowedDestinations, true)) {
                $destination = 'dashboard';
            }
            header('Location: ?page=' . rawurlencode($destination));
            exit;
        }
    }
}
