<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Authentication;
use PDO;

final class AccountController
{
    public static function handle(PDO $pdo, string $pageKey, array $user): array
    {
        $state = [
            'adminError' => null,
            'adminValues' => ['full_name' => '', 'email' => ''],
            'adminSuccess' => null,
            'accounts' => [],
            'accountsError' => null,
            'accountsSuccess' => null,
        ];

        if ($pageKey === 'add-admin') {
            self::createAdmin($pdo, $user, $state);
        } elseif ($pageKey === 'users') {
            self::manageAccounts($pdo, $user, $state);
        }

        return $state;
    }

    private static function createAdmin(PDO $pdo, array $user, array &$state): void
    {
        if (!in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true)) {
            http_response_code(403);
            $state['adminError'] = 'Vetëm administratori ose superadministratori mund të krijojë llogari administratori.';
            return;
        }

        $state['adminSuccess'] = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        $fullName = $_POST['full_name'] ?? null;
        $email = $_POST['email'] ?? null;
        $password = $_POST['password'] ?? null;
        $passwordConfirmation = $_POST['password_confirmation'] ?? null;
        if (is_string($fullName)) {
            $state['adminValues']['full_name'] = trim($fullName);
        }
        if (is_string($email)) {
            $state['adminValues']['email'] = trim($email);
        }

        if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $state['adminError'] = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
        } elseif (!is_string($fullName) || trim($fullName) === '' || mb_strlen(trim($fullName)) > 150) {
            http_response_code(422);
            $state['adminError'] = 'Emri është i detyrueshëm dhe duhet të ketë maksimumi 150 karaktere.';
        } elseif (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false || mb_strlen(trim($email)) > 150) {
            http_response_code(422);
            $state['adminError'] = 'Shkruaj një adresë email të vlefshme (maksimumi 150 karaktere).';
        } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
            http_response_code(422);
            $state['adminError'] = 'Fjalëkalimi duhet të ketë të paktën 12 karaktere dhe maksimumi 72 bajte.';
        } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
            http_response_code(422);
            $state['adminError'] = 'Fjalëkalimet nuk përputhen.';
        } else {
            $normalizedEmail = mb_strtolower(trim($email));
            $userId = Authentication::createAdmin($pdo, trim($fullName), $normalizedEmail, $password);
            if ($userId === null) {
                http_response_code(409);
                $state['adminError'] = 'Ky email është përdorur tashmë për një llogari.';
                return;
            }

            try {
                Authentication::sendVerificationCode($pdo, $userId, $normalizedEmail, trim($fullName));
                $_SESSION['flash_message'] = 'Llogaria e re ADMIN u krijua. Kodi i verifikimit u dërgua në email-in e përdoruesit.';
            } catch (\RuntimeException $exception) {
                $_SESSION['flash_message'] = 'Llogaria ADMIN u krijua, por kodi nuk u dërgua. Përdoruesi mund ta kërkojë sërish te faqja e verifikimit.';
            }
            header('Location: ?page=add-admin');
            exit;
        }
    }

    private static function manageAccounts(PDO $pdo, array $user, array &$state): void
    {
        if (!in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true)) {
            http_response_code(403);
            $state['accountsError'] = 'Nuk ke leje të menaxhosh llogaritë.';
            return;
        }

        $state['accountsSuccess'] = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::handleAccountAction($pdo, $user, $state);
        }

        $accountsStatement = $pdo->prepare(
            'SELECT u.id, u.full_name, u.email, u.role, u.is_active, u.email_verified_at,
                u.created_at, (SELECT COUNT(*) FROM transports t WHERE t.created_by = u.id) AS transport_count
            FROM users u
            WHERE (:is_superadmin = 1
                    AND (u.role <> :protected_role OR u.id = :current_user_id))
                OR (:is_admin = 1 AND u.role = :dispatcher_role)
            ORDER BY u.role, u.full_name, u.id'
        );
        $accountsStatement->execute([
            'is_superadmin' => $user['role'] === 'SUPERADMIN' ? 1 : 0,
            'protected_role' => 'SUPERADMIN',
            'current_user_id' => (int) $user['id'],
            'is_admin' => $user['role'] === 'ADMIN' ? 1 : 0,
            'dispatcher_role' => 'DISPATCHER',
        ]);
        $state['accounts'] = $accountsStatement->fetchAll();
    }

    private static function handleAccountAction(PDO $pdo, array $user, array &$state): void
    {
        $action = $_POST['account_action'] ?? null;
        $accountId = filter_var(
            $_POST['id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $state['accountsError'] = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
        } elseif (!is_string($action) || !in_array($action, ['update_email', 'reset_password', 'delete'], true)) {
            http_response_code(422);
            $state['accountsError'] = 'Veprimi i kërkuar nuk është i vlefshëm.';
        } elseif ($accountId === false) {
            http_response_code(422);
            $state['accountsError'] = 'Llogaria e zgjedhur nuk është e vlefshme.';
        } else {
            $accountStatement = $pdo->prepare(
                'SELECT id, full_name, email, role FROM users WHERE id = :id LIMIT 1'
            );
            $accountStatement->execute(['id' => $accountId]);
            $account = $accountStatement->fetch();
            if ($account === false) {
                http_response_code(404);
                $state['accountsError'] = 'Llogaria nuk u gjet; lista mund të jetë përditësuar.';
            } elseif (
                $account['role'] === 'SUPERADMIN'
                && !($user['role'] === 'SUPERADMIN' && (int) $account['id'] === (int) $user['id'])
            ) {
                http_response_code(409);
                $state['accountsError'] = 'Llogaria e superadministratorit është e mbrojtur.';
            } elseif ($user['role'] === 'ADMIN' && $account['role'] !== 'DISPATCHER') {
                http_response_code(403);
                $state['accountsError'] = 'Administratorët mund të menaxhojnë vetëm llogaritë e dispeçerëve.';
            } elseif ($action === 'update_email') {
                self::updateEmail($pdo, $account, $accountId, $user, $state);
            } elseif ($action === 'reset_password') {
                self::resetPassword($pdo, $accountId, $user, $state);
            } else {
                self::deleteAccount($pdo, $accountId, $user, $state);
            }
        }
    }

    private static function updateEmail(
        PDO $pdo,
        array $account,
        int $accountId,
        array $user,
        array &$state
    ): void
    {
        $email = $_POST['email'] ?? null;
        if (
            !is_string($email)
            || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false
            || mb_strlen(trim($email)) > 150
        ) {
            http_response_code(422);
            $state['accountsError'] = 'Shkruaj një adresë email të vlefshme (maksimumi 150 karaktere).';
        } elseif (mb_strtolower(trim($email)) === mb_strtolower($account['email'])) {
            http_response_code(422);
            $state['accountsError'] = 'Email-i i ri duhet të jetë i ndryshëm nga ai aktual.';
        } else {
            $newEmail = mb_strtolower(trim($email));
            try {
                $updateEmail = $pdo->prepare(
                    'UPDATE users
                    SET email = :email, is_active = 0, email_verified_at = NULL
                    WHERE id = :id
                        AND (role <> :protected_role OR id = :current_user_id)'
                );
                $updateEmail->execute([
                    'email' => $newEmail,
                    'id' => $accountId,
                    'protected_role' => 'SUPERADMIN',
                    'current_user_id' => (int) $user['id'],
                ]);
            } catch (\PDOException $exception) {
                if (($exception->errorInfo[1] ?? null) !== 1062) {
                    throw $exception;
                }
                http_response_code(409);
                $state['accountsError'] = 'Ky email është përdorur tashmë për një llogari.';
            }

            if ($state['accountsError'] === null && $updateEmail->rowCount() === 1) {
                try {
                    Authentication::sendVerificationCode(
                        $pdo,
                        $accountId,
                        $newEmail,
                        $account['full_name']
                    );
                    $_SESSION['flash_message'] = 'Email-i u ndryshua. Llogaria do të aktivizohet pasi të verifikohet email-i i ri.';
                } catch (\RuntimeException $exception) {
                    $_SESSION['flash_message'] = 'Email-i u ndryshua, por kodi i verifikimit nuk u dërgua. Përdoruesi mund ta kërkojë sërish te faqja e verifikimit.';
                }
                header('Location: ?page=users');
                exit;
            }
        }
    }

    private static function resetPassword(PDO $pdo, int $accountId, array $user, array &$state): void
    {
        $password = $_POST['password'] ?? null;
        $passwordConfirmation = $_POST['password_confirmation'] ?? null;
        if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
            http_response_code(422);
            $state['accountsError'] = 'Fjalëkalimi duhet të ketë të paktën 12 karaktere dhe maksimumi 72 bajte.';
        } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
            http_response_code(422);
            $state['accountsError'] = 'Fjalëkalimet nuk përputhen.';
        } else {
            $updatePassword = $pdo->prepare(
                'UPDATE users SET password_hash = :password_hash
                WHERE id = :id
                    AND (role <> :protected_role OR id = :current_user_id)'
            );
            $updatePassword->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'id' => $accountId,
                'protected_role' => 'SUPERADMIN',
                'current_user_id' => (int) $user['id'],
            ]);
            if ($updatePassword->rowCount() === 1) {
                $_SESSION['flash_message'] = 'Fjalëkalimi u ndryshua me sukses.';
                header('Location: ?page=users');
                exit;
            }
            http_response_code(409);
            $state['accountsError'] = 'Fjalëkalimi nuk u ndryshua. Rifresko listën dhe provo përsëri.';
        }
    }

    private static function deleteAccount(PDO $pdo, int $accountId, array $user, array &$state): void
    {
        $transportStatement = $pdo->prepare(
            'SELECT COUNT(*) FROM transports WHERE created_by = :id'
        );
        $transportStatement->execute(['id' => $accountId]);
        if ((int) $transportStatement->fetchColumn() > 0) {
            http_response_code(409);
            $state['accountsError'] = 'Kjo llogari ka krijuar transporte. Nuk mund të fshihet për të ruajtur historikun e transportit.';
            return;
        }

        try {
            $deleteStatement = $pdo->prepare(
                "DELETE FROM users
                WHERE id = :id AND id <> :current_user_id
                    AND role <> 'SUPERADMIN'
                    AND (:is_superadmin = 1 OR role = 'DISPATCHER')"
            );
            $deleteStatement->execute([
                'id' => $accountId,
                'current_user_id' => (int) $user['id'],
                'is_superadmin' => $user['role'] === 'SUPERADMIN' ? 1 : 0,
            ]);
        } catch (\PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            http_response_code(409);
            $state['accountsError'] = 'Llogaria lidhet me të dhëna ekzistuese dhe nuk mund të fshihet.';
            return;
        }

        if ($deleteStatement->rowCount() !== 1) {
            http_response_code(409);
            $state['accountsError'] = 'Llogaria nuk u fshi. Rifresko listën dhe provo përsëri.';
            return;
        }

        $_SESSION['flash_message'] = 'Llogaria u fshi me sukses.';
        header('Location: ?page=users');
        exit;
    }
}
