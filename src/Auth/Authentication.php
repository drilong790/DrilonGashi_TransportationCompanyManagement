<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;

final class Authentication
{
    private const VERIFICATION_RESEND_DELAY_SECONDS = 60;
    private const VERIFICATION_MAX_ATTEMPTS = 5;

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function isValidCsrfToken(mixed $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function currentUser(PDO $pdo): ?array
    {
        $userId = filter_var(
            $_SESSION['user_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if ($userId === false) {
            unset($_SESSION['user_id']);
            return null;
        }

        $statement = $pdo->prepare(
            "SELECT id, full_name, email, role FROM users
            WHERE id = :id AND is_active = 1 AND email_verified_at IS NOT NULL
                AND role IN ('ADMIN', 'DISPATCHER')
            LIMIT 1"
        );
        $statement->execute(['id' => $userId]);
        $user = $statement->fetch();
        if ($user === false) {
            unset($_SESSION['user_id']);
            return null;
        }
        return $user;
    }

    public static function attempt(PDO $pdo, string $email, string $password): ?array
    {
        $statement = $pdo->prepare(
            "SELECT id, full_name, email, password_hash, role FROM users
            WHERE email = :email AND is_active = 1 AND email_verified_at IS NOT NULL
                AND role IN ('ADMIN', 'DISPATCHER')
            LIMIT 1"
        );
        $statement->execute(['email' => $email]);
        $user = $statement->fetch();

        if ($user === false || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $update = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
            $update->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $user['id'],
            ]);
        }

        unset($user['password_hash']);
        return $user;
    }

    public static function register(PDO $pdo, string $fullName, string $email, string $password): ?int
    {
        return self::createAccount($pdo, $fullName, $email, $password, 'DISPATCHER');
    }

    public static function createAdmin(PDO $pdo, string $fullName, string $email, string $password): ?int
    {
        return self::createAccount($pdo, $fullName, $email, $password, 'ADMIN');
    }

    private static function createAccount(
        PDO $pdo,
        string $fullName,
        string $email,
        string $password,
        string $role
    ): ?int {
        $statement = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at)
            VALUES (:full_name, :email, :password_hash, :role, 0, NULL)'
        );

        try {
            $statement->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
            ]);
        } catch (\PDOException $exception) {
            if (($exception->errorInfo[1] ?? null) === 1062) {
                return null;
            }
            throw $exception;
        }

        return (int) $pdo->lastInsertId();
    }

    public static function sendVerificationCode(PDO $pdo, int $userId, string $email, string $fullName): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $statement = $pdo->prepare(
            'INSERT INTO email_verifications (user_id, code_hash, expires_at, attempts, last_sent_at)
            VALUES (:user_id, :code_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 10 MINUTE), 0, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
                code_hash = VALUES(code_hash),
                expires_at = VALUES(expires_at),
                attempts = 0,
                last_sent_at = CURRENT_TIMESTAMP'
        );
        $statement->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $statement->bindValue(':code_hash', password_hash($code, PASSWORD_DEFAULT), PDO::PARAM_STR);
        $statement->execute();

        try {
            (new \App\Mail\SmtpMailer())->sendVerificationCode($email, $fullName, $code);
        } catch (\Throwable $exception) {
            $cause = $exception->getPrevious();
            error_log(
                'Sending email verification failed: '
                . ($cause === null ? $exception->getMessage() : $cause->getMessage())
            );
            throw new \RuntimeException(
                'Kodi i verifikimit nuk u dërgua. Provo ridërgimin pas pak minutash.',
                0,
                $exception
            );
        }
    }

    public static function resendVerificationCode(PDO $pdo, string $email): bool
    {
        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'SELECT u.id, u.full_name, u.email,
                    TIMESTAMPDIFF(SECOND, v.last_sent_at, CURRENT_TIMESTAMP) AS seconds_since_last_sent
                FROM users u
                LEFT JOIN email_verifications v ON v.user_id = u.id
                WHERE u.email = :email AND u.is_active = 0 AND u.email_verified_at IS NULL
                LIMIT 1 FOR UPDATE'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();
            if ($user === false) {
                $pdo->commit();
                return true;
            }
            if (
                $user['seconds_since_last_sent'] !== null
                && (int) $user['seconds_since_last_sent'] < self::VERIFICATION_RESEND_DELAY_SECONDS
            ) {
                $pdo->commit();
                return false;
            }

            try {
                self::sendVerificationCode($pdo, (int) $user['id'], $user['email'], $user['full_name']);
            } catch (\RuntimeException $exception) {
                $pdo->commit();
                throw $exception;
            }
            $pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function verifyEmail(PDO $pdo, string $email, string $code): bool
    {
        if (preg_match('/^\d{6}$/D', $code) !== 1) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            $statement = $pdo->prepare(
                'SELECT u.id, v.code_hash, (v.expires_at > CURRENT_TIMESTAMP) AS not_expired, v.attempts
                FROM users u
                JOIN email_verifications v ON v.user_id = u.id
                WHERE u.email = :email AND u.is_active = 0 AND u.email_verified_at IS NULL
                LIMIT 1 FOR UPDATE'
            );
            $statement->execute(['email' => $email]);
            $verification = $statement->fetch();

            if (
                $verification === false
                || (int) $verification['not_expired'] !== 1
                || (int) $verification['attempts'] >= self::VERIFICATION_MAX_ATTEMPTS
            ) {
                $pdo->commit();
                return false;
            }

            if (!password_verify($code, $verification['code_hash'])) {
                $update = $pdo->prepare(
                    'UPDATE email_verifications SET attempts = attempts + 1 WHERE user_id = :user_id'
                );
                $update->execute(['user_id' => $verification['id']]);
                $pdo->commit();
                return false;
            }

            $activate = $pdo->prepare(
                'UPDATE users
                SET email_verified_at = CURRENT_TIMESTAMP, is_active = 1
                WHERE id = :user_id AND is_active = 0 AND email_verified_at IS NULL'
            );
            $activate->execute(['user_id' => $verification['id']]);
            $delete = $pdo->prepare('DELETE FROM email_verifications WHERE user_id = :user_id');
            $delete->execute(['user_id' => $verification['id']]);
            $pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }

    public static function signOut(): void
    {
        unset($_SESSION['user_id'], $_SESSION['flash_message'], $_SESSION['redirect_after_login']);
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
