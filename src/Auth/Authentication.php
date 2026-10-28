<?php

declare(strict_types=1);

namespace App\Auth;

use PDO;

final class Authentication
{
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
            WHERE id = :id AND is_active = 1 AND role IN ('ADMIN', 'DISPATCHER')
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
            WHERE email = :email AND is_active = 1 AND role IN ('ADMIN', 'DISPATCHER')
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

    public static function register(PDO $pdo, string $fullName, string $email, string $password): bool
    {
        return self::createAccount($pdo, $fullName, $email, $password, 'DISPATCHER');
    }

    public static function createAdmin(PDO $pdo, string $fullName, string $email, string $password): bool
    {
        return self::createAccount($pdo, $fullName, $email, $password, 'ADMIN');
    }

    private static function createAccount(
        PDO $pdo,
        string $fullName,
        string $email,
        string $password,
        string $role
    ): bool {
        $statement = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, is_active)
            VALUES (:full_name, :email, :password_hash, :role, 1)'
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
                return false;
            }
            throw $exception;
        }

        return true;
    }

    public static function signOut(): void
    {
        unset($_SESSION['user_id'], $_SESSION['flash_message'], $_SESSION['redirect_after_login']);
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
