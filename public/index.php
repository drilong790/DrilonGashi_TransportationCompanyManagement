<?php

declare(strict_types=1);

use App\Auth\Authentication;
use App\Database\Connection;
use App\Modules\CrudModule;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Auth/Authentication.php';
require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Modules/CrudModule.php';

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$pages = [
    'dashboard' => [
        'title' => 'Paneli kryesor',
        'description' => 'Përmbledhje e moduleve të menaxhimit të transportit.',
        'view' => 'dashboard.php',
    ],
    'transports' => [
        'title' => 'Transportet',
        'description' => 'Planifiko dhe ndiq transportet e kompanisë.',
        'view' => 'module.php',
    ],
    'customers' => [
        'title' => 'Klientët',
        'description' => 'Menaxho informacionin e klientëve të kompanisë.',
        'view' => 'module.php',
    ],
    'drivers' => [
        'title' => 'Shoferët',
        'description' => 'Menaxho shoferët dhe statusin e tyre.',
        'view' => 'module.php',
    ],
    'vehicles' => [
        'title' => 'Automjetet',
        'description' => 'Menaxho automjetet dhe kapacitetet e tyre.',
        'view' => 'module.php',
    ],
    'login' => [
        'title' => 'Hyr në sistem',
        'description' => 'Identifikohu për të vazhduar.',
        'view' => 'login.php',
    ],
    'register' => [
        'title' => 'Krijo llogari',
        'description' => 'Regjistro një llogari dispeçeri.',
        'view' => 'register.php',
    ],
    'verify-email' => [
        'title' => 'Verifiko email-in',
        'description' => 'Konfirmo adresën e email-it për të aktivizuar llogarinë.',
        'view' => 'verify-email.php',
    ],
    'add-admin' => [
        'title' => 'Shto administrator',
        'description' => 'Krijo një llogari të re administratori.',
        'view' => 'add-admin.php',
    ],
    'users' => [
        'title' => 'Menaxho llogaritë',
        'description' => 'Shiko përdoruesit dhe fshi llogari që nuk kanë transport të regjistruar.',
        'view' => 'users.php',
    ],
    'not_found' => [
        'title' => 'Faqja nuk u gjet',
        'description' => 'Adresa e kërkuar nuk është e disponueshme.',
        'view' => 'not-found.php',
    ],
];

$navigationItems = [
    'dashboard' => 'Paneli kryesor',
    'transports' => 'Transportet',
    'customers' => 'Klientët',
    'drivers' => 'Shoferët',
    'vehicles' => 'Automjetet',
];

$requestedPage = $_GET['page'] ?? 'dashboard';
if (is_string($requestedPage) && ($requestedPage === 'logout' || isset($pages[$requestedPage]))) {
    $pageKey = $requestedPage;
} else {
    http_response_code(404);
    $pageKey = 'not_found';
}
$loginError = null;
$registerError = null;
$registerValues = ['full_name' => '', 'email' => ''];
$verificationEmail = '';
$verificationError = null;
$verificationMessage = null;
$adminError = null;
$adminValues = ['full_name' => '', 'email' => ''];
$adminSuccess = null;
$accounts = [];
$accountsError = null;
$accountsSuccess = null;
$databaseError = null;
$dashboardStats = null;
$dashboardReport = [];
$dashboardReportTotal = 0;
$dashboardReportFrom = date('Y-m-01');
$dashboardReportTo = date('Y-m-d');
$dashboardReportError = null;
$moduleData = null;
$user = null;
$csrfToken = Authentication::csrfToken();

if ($pageKey === 'logout') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Kërkesa për dalje nuk mund të verifikohej.';
        exit;
    }

    Authentication::signOut();
    header('Location: ?page=login');
    exit;
}

try {
    $pdo = Connection::create();

    if (in_array($pageKey, ['login', 'register', 'verify-email'], true)) {
        $user = Authentication::currentUser($pdo);
        if ($user !== null && $pageKey !== 'verify-email') {
            header('Location: ?page=dashboard');
            exit;
        }

        $verificationEmail = is_string($_SESSION['verification_email'] ?? null)
            ? $_SESSION['verification_email']
            : '';
        $verificationMessage = $_SESSION['verification_message'] ?? null;
        unset($_SESSION['verification_message']);

        if ($pageKey === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $fullName = $_POST['full_name'] ?? null;
            $email = $_POST['email'] ?? null;
            $password = $_POST['password'] ?? null;
            $passwordConfirmation = $_POST['password_confirmation'] ?? null;
            if (is_string($fullName)) {
                $registerValues['full_name'] = trim($fullName);
            }
            if (is_string($email)) {
                $registerValues['email'] = trim($email);
            }

            if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(400);
                $registerError = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
            } elseif (!is_string($fullName) || trim($fullName) === '' || mb_strlen(trim($fullName)) > 150) {
                http_response_code(422);
                $registerError = 'Emri është i detyrueshëm dhe duhet të ketë maksimumi 150 karaktere.';
            } elseif (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false || mb_strlen(trim($email)) > 150) {
                http_response_code(422);
                $registerError = 'Shkruaj një adresë email të vlefshme (maksimumi 150 karaktere).';
            } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
                http_response_code(422);
                $registerError = 'Fjalëkalimi duhet të ketë të paktën 12 karaktere dhe maksimumi 72 bajte.';
            } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
                http_response_code(422);
                $registerError = 'Fjalëkalimet nuk përputhen.';
            } else {
                $normalizedEmail = mb_strtolower(trim($email));
                $userId = Authentication::register($pdo, trim($fullName), $normalizedEmail, $password);
                if ($userId === null) {
                    http_response_code(409);
                    $registerError = 'Nuk mund të krijohet llogaria me këtë email. Kontrollo të dhënat ose provo të hysh.';
                } else {
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
        } elseif ($pageKey === 'verify-email' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? null;
            $action = $_POST['action'] ?? null;
            $verificationEmail = is_string($email) ? mb_strtolower(trim($email)) : '';
            if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(400);
                $verificationError = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
            } elseif (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
                http_response_code(422);
                $verificationError = 'Shkruaj një adresë email të vlefshme.';
            } elseif ($action === 'verify') {
                $code = $_POST['code'] ?? null;
                if (!is_string($code) || !Authentication::verifyEmail($pdo, $verificationEmail, trim($code))) {
                    http_response_code(422);
                    $verificationError = 'Kodi nuk është i vlefshëm, ka skaduar ose është tejkaluar numri i tentativave.';
                } else {
                    unset($_SESSION['verification_email']);
                    $_SESSION['flash_message'] = 'Email-i u verifikua. Tani mund të hysh në llogari.';
                    header('Location: ?page=login');
                    exit;
                }
            } elseif ($action === 'resend') {
                try {
                    $sent = Authentication::resendVerificationCode($pdo, $verificationEmail);
                    $verificationMessage = $sent
                        ? 'Nëse ekziston një llogari e paverifikuar me këtë email, kodi u dërgua. Prit të paktën 60 sekonda para një kërkese tjetër.'
                        : 'Prit të paktën 60 sekonda para se të kërkosh një kod tjetër.';
                } catch (\RuntimeException $exception) {
                    $verificationError = 'Kodi nuk mund të dërgohej. Kontrollo konfigurimin SMTP dhe provo përsëri pas pak.';
                }
            } else {
                http_response_code(400);
                $verificationError = 'Veprimi i kërkuar nuk është i vlefshëm.';
            }
            $_SESSION['verification_email'] = $verificationEmail;
        } elseif ($pageKey === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? null;
            $password = $_POST['password'] ?? null;
            if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(400);
                $loginError = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
            } elseif (!is_string($email) || !is_string($password) || trim($email) === '' || $password === '') {
                http_response_code(422);
                $loginError = 'Shkruaj email-in dhe fjalëkalimin.';
            } else {
                $user = Authentication::attempt($pdo, trim($email), $password);
                if ($user === null) {
                    http_response_code(401);
                    $loginError = 'Email-i ose fjalëkalimi nuk është i saktë, ose llogaria është çaktivizuar.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $destination = $_SESSION['redirect_after_login'] ?? 'dashboard';
                    unset($_SESSION['redirect_after_login']);
                    $allowedDestinations = array_merge(
                        array_keys($navigationItems),
                        ['add-admin', 'users', 'not_found']
                    );
                    if (!is_string($destination) || !in_array($destination, $allowedDestinations, true)) {
                        $destination = 'dashboard';
                    }
                    header('Location: ?page=' . rawurlencode($destination));
                    exit;
                }
            }
        }
    } else {
        $user = Authentication::currentUser($pdo);
        if ($user === null) {
            $_SESSION['redirect_after_login'] = $pageKey;
            header('Location: ?page=login');
            exit;
        }

        if ($pageKey === 'add-admin') {
            if (!in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true)) {
                http_response_code(403);
                $adminError = 'Vetëm administratori ose superadministratori mund të krijojë llogari administratori.';
            } else {
                $adminSuccess = $_SESSION['flash_message'] ?? null;
                unset($_SESSION['flash_message']);

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $fullName = $_POST['full_name'] ?? null;
                    $email = $_POST['email'] ?? null;
                    $password = $_POST['password'] ?? null;
                    $passwordConfirmation = $_POST['password_confirmation'] ?? null;
                    if (is_string($fullName)) {
                        $adminValues['full_name'] = trim($fullName);
                    }
                    if (is_string($email)) {
                        $adminValues['email'] = trim($email);
                    }

                    if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
                        http_response_code(400);
                        $adminError = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
                    } elseif (!is_string($fullName) || trim($fullName) === '' || mb_strlen(trim($fullName)) > 150) {
                        http_response_code(422);
                        $adminError = 'Emri është i detyrueshëm dhe duhet të ketë maksimumi 150 karaktere.';
                    } elseif (!is_string($email) || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false || mb_strlen(trim($email)) > 150) {
                        http_response_code(422);
                        $adminError = 'Shkruaj një adresë email të vlefshme (maksimumi 150 karaktere).';
                    } elseif (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
                        http_response_code(422);
                        $adminError = 'Fjalëkalimi duhet të ketë të paktën 12 karaktere dhe maksimumi 72 bajte.';
                    } elseif (!is_string($passwordConfirmation) || !hash_equals($password, $passwordConfirmation)) {
                        http_response_code(422);
                        $adminError = 'Fjalëkalimet nuk përputhen.';
                    } else {
                        $normalizedEmail = mb_strtolower(trim($email));
                        $userId = Authentication::createAdmin($pdo, trim($fullName), $normalizedEmail, $password);
                        if ($userId === null) {
                            http_response_code(409);
                            $adminError = 'Ky email është përdorur tashmë për një llogari.';
                        } else {
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
                }
            }
        } elseif ($pageKey === 'users') {
            if (!in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true)) {
                http_response_code(403);
                $accountsError = 'Nuk ke leje të menaxhosh llogaritë.';
            } else {
                $accountsSuccess = $_SESSION['flash_message'] ?? null;
                unset($_SESSION['flash_message']);

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $action = $_POST['account_action'] ?? null;
                    $accountId = filter_var(
                        $_POST['id'] ?? null,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );
                    if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
                        http_response_code(400);
                        $accountsError = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
                    } elseif (!is_string($action) || !in_array($action, ['update_email', 'reset_password', 'delete'], true)) {
                        http_response_code(422);
                        $accountsError = 'Veprimi i kërkuar nuk është i vlefshëm.';
                    } elseif ($accountId === false) {
                        http_response_code(422);
                        $accountsError = 'Llogaria e zgjedhur nuk është e vlefshme.';
                    } else {
                        $accountStatement = $pdo->prepare(
                            'SELECT id, full_name, email, role FROM users WHERE id = :id LIMIT 1'
                        );
                        $accountStatement->execute(['id' => $accountId]);
                        $accountToDelete = $accountStatement->fetch();
                        if ($accountToDelete === false) {
                            http_response_code(404);
                            $accountsError = 'Llogaria nuk u gjet; lista mund të jetë përditësuar.';
                        } elseif (
                            $accountToDelete['role'] === 'SUPERADMIN'
                            && !(
                                $user['role'] === 'SUPERADMIN'
                                && (int) $accountToDelete['id'] === (int) $user['id']
                            )
                        ) {
                            http_response_code(409);
                            $accountsError = 'Llogaria e superadministratorit është e mbrojtur.';
                        } elseif ($user['role'] === 'ADMIN' && $accountToDelete['role'] !== 'DISPATCHER') {
                            http_response_code(403);
                            $accountsError = 'Administratorët mund të menaxhojnë vetëm llogaritë e dispeçerëve.';
                        } elseif ($action === 'update_email') {
                            $email = $_POST['email'] ?? null;
                            if (
                                !is_string($email)
                                || filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false
                                || mb_strlen(trim($email)) > 150
                            ) {
                                http_response_code(422);
                                $accountsError = 'Shkruaj një adresë email të vlefshme (maksimumi 150 karaktere).';
                            } elseif (mb_strtolower(trim($email)) === mb_strtolower($accountToDelete['email'])) {
                                http_response_code(422);
                                $accountsError = 'Email-i i ri duhet të jetë i ndryshëm nga ai aktual.';
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
                                    $accountsError = 'Ky email është përdorur tashmë për një llogari.';
                                }

                                if ($accountsError === null && $updateEmail->rowCount() === 1) {
                                    try {
                                        Authentication::sendVerificationCode(
                                            $pdo,
                                            (int) $accountId,
                                            $newEmail,
                                            $accountToDelete['full_name']
                                        );
                                        $_SESSION['flash_message'] = 'Email-i u ndryshua. Llogaria do të aktivizohet pasi të verifikohet email-i i ri.';
                                    } catch (\RuntimeException $exception) {
                                        $_SESSION['flash_message'] = 'Email-i u ndryshua, por kodi i verifikimit nuk u dërgua. Përdoruesi mund ta kërkojë sërish te faqja e verifikimit.';
                                    }
                                    header('Location: ?page=users');
                                    exit;
                                }
                            }
                        } elseif ($action === 'reset_password') {
                            $password = $_POST['password'] ?? null;
                            $passwordConfirmation = $_POST['password_confirmation'] ?? null;
                            if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72) {
                                http_response_code(422);
                                $accountsError = 'Fjalëkalimi duhet të ketë të paktën 12 karaktere dhe maksimumi 72 bajte.';
                            } elseif (
                                !is_string($passwordConfirmation)
                                || !hash_equals($password, $passwordConfirmation)
                            ) {
                                http_response_code(422);
                                $accountsError = 'Fjalëkalimet nuk përputhen.';
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
                                $accountsError = 'Fjalëkalimi nuk u ndryshua. Rifresko listën dhe provo përsëri.';
                            }
                        } else {
                            $transportStatement = $pdo->prepare(
                                'SELECT COUNT(*) FROM transports WHERE created_by = :id'
                            );
                            $transportStatement->execute(['id' => $accountId]);
                            if ((int) $transportStatement->fetchColumn() > 0) {
                                http_response_code(409);
                                $accountsError = 'Kjo llogari ka krijuar transporte. Nuk mund të fshihet për të ruajtur historikun e transportit.';
                            } else {
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
                                    $accountsError = 'Llogaria lidhet me të dhëna ekzistuese dhe nuk mund të fshihet.';
                                }

                                if ($accountsError === null) {
                                    if ($deleteStatement->rowCount() !== 1) {
                                        http_response_code(409);
                                        $accountsError = 'Llogaria nuk u fshi. Rifresko listën dhe provo përsëri.';
                                    } else {
                                        $_SESSION['flash_message'] = 'Llogaria u fshi me sukses.';
                                        header('Location: ?page=users');
                                        exit;
                                    }
                                }
                            }
                        }
                    }
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
                $accounts = $accountsStatement->fetchAll();
            }
        } elseif (in_array($pageKey, ['customers', 'drivers', 'vehicles', 'transports'], true)) {
            $moduleData = CrudModule::handle($pdo, $pageKey, (int) $user['id'], $user['role']);
        } elseif ($pageKey === 'dashboard') {
            $statement = $pdo->query(
                'SELECT
                    (SELECT COUNT(*) FROM transports) AS transports,
                    (SELECT COUNT(*) FROM customers) AS customers,
                    (SELECT COUNT(*) FROM drivers) AS drivers,
                    (SELECT COUNT(*) FROM vehicles) AS vehicles'
            );
            $dashboardStats = $statement->fetch();

            $dateFrom = $_GET['date_from'] ?? $dashboardReportFrom;
            $dateTo = $_GET['date_to'] ?? $dashboardReportTo;
            $isValidReportDate = static function (mixed $value): bool {
                if (!is_string($value)) {
                    return false;
                }
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                return $date !== false && $date->format('Y-m-d') === $value;
            };

            if (!$isValidReportDate($dateFrom) || !$isValidReportDate($dateTo)) {
                http_response_code(422);
                $dashboardReportError = 'Zgjidh data të vlefshme për raportin.';
            } elseif ($dateFrom > $dateTo) {
                http_response_code(422);
                $dashboardReportError = 'Data e fillimit nuk mund të jetë pas datës së mbarimit.';
            } else {
                $dashboardReportFrom = $dateFrom;
                $dashboardReportTo = $dateTo;
                $reportStatement = $pdo->prepare(
                    'SELECT status, COUNT(*) AS total
                    FROM transports
                    WHERE scheduled_date >= :date_from
                        AND scheduled_date < DATE_ADD(:date_to, INTERVAL 1 DAY)
                    GROUP BY status'
                );
                $reportStatement->execute([
                    'date_from' => $dashboardReportFrom . ' 00:00:00',
                    'date_to' => $dashboardReportTo,
                ]);
                $statusCounts = array_column($reportStatement->fetchAll(), 'total', 'status');
                $reportLabels = [
                    'PLANNED' => 'Planifikuar',
                    'IN_PROGRESS' => 'Në proces',
                    'COMPLETED' => 'Përfunduar',
                    'CANCELLED' => 'Anuluar',
                ];
                foreach ($reportLabels as $status => $label) {
                    $total = (int) ($statusCounts[$status] ?? 0);
                    $dashboardReport[] = ['status' => $status, 'label' => $label, 'total' => $total];
                    $dashboardReportTotal += $total;
                }

                if (($_GET['export'] ?? null) === 'csv') {
                    $exportStatement = $pdo->prepare(
                        'SELECT t.id, c.company_name AS customer_name, d.full_name AS driver_name,
                            v.plate_number, t.pickup_address, t.destination_address, t.cargo_description,
                            t.scheduled_date, t.status, u.full_name AS created_by_name
                        FROM transports t
                        JOIN customers c ON c.id = t.customer_id
                        LEFT JOIN drivers d ON d.id = t.driver_id
                        LEFT JOIN vehicles v ON v.id = t.vehicle_id
                        JOIN users u ON u.id = t.created_by
                        WHERE t.scheduled_date >= :date_from
                            AND t.scheduled_date < DATE_ADD(:date_to, INTERVAL 1 DAY)
                        ORDER BY t.scheduled_date DESC, t.id DESC'
                    );
                    $exportStatement->execute([
                        'date_from' => $dashboardReportFrom . ' 00:00:00',
                        'date_to' => $dashboardReportTo,
                    ]);
                    header('Content-Type: text/csv; charset=utf-8');
                    header(
                        'Content-Disposition: attachment; filename="raporti-transportit-'
                        . $dashboardReportFrom . '-deri-' . $dashboardReportTo . '.csv"'
                    );
                    header('X-Content-Type-Options: nosniff');
                    $output = fopen('php://output', 'wb');
                    if ($output === false) {
                        throw new \RuntimeException('Skedari CSV i raportit nuk mund të krijohej.');
                    }
                    fwrite($output, "\xEF\xBB\xBF");
                    fputcsv($output, ['ID', 'Klienti', 'Shoferi', 'Targa', 'Nisja', 'Destinacioni', 'Ngarkesa', 'Data', 'Statusi', 'Regjistruar nga']);
                    $safeCsvValue = static function (?string $value): string {
                        $value ??= '';
                        return preg_match('/^[\s]*[=+\-@]/u', $value) === 1 ? "'" . $value : $value;
                    };
                    $statusCsvLabels = [
                        'PLANNED' => 'Planifikuar',
                        'IN_PROGRESS' => 'Në proces',
                        'COMPLETED' => 'Përfunduar',
                        'CANCELLED' => 'Anuluar',
                    ];
                    while ($row = $exportStatement->fetch()) {
                        fputcsv($output, [
                            $row['id'],
                            $safeCsvValue($row['customer_name']),
                            $safeCsvValue($row['driver_name']),
                            $safeCsvValue($row['plate_number']),
                            $safeCsvValue($row['pickup_address']),
                            $safeCsvValue($row['destination_address']),
                            $safeCsvValue($row['cargo_description']),
                            $row['scheduled_date'],
                            $statusCsvLabels[$row['status']] ?? $row['status'],
                            $safeCsvValue($row['created_by_name']),
                        ]);
                    }
                    fclose($output);
                    exit;
                }
            }
        }
    }
} catch (\PDOException $exception) {
    error_log('Database request failed: ' . $exception->getMessage());
    http_response_code(503);
    if ($pageKey === 'login') {
        $loginError = 'Nuk mund të lidhemi me databazën. Kontrollo MySQL dhe konfigurimin e databazës.';
    } elseif ($pageKey === 'register') {
        $registerError = 'Nuk mund të krijohet llogaria për shkak të lidhjes me databazën. Provo përsëri.';
    } elseif ($pageKey === 'verify-email') {
        $verificationError = 'Nuk mund të kryhet verifikimi për shkak të lidhjes me databazën. Provo përsëri.';
    } elseif ($pageKey === 'dashboard') {
        $dashboardReportError = 'Nuk mund të ngarkohet raporti për shkak të lidhjes me databazën.';
    } else {
        $databaseError = 'Nuk mund të kryhet kërkesa për shkak të lidhjes me databazën. Kontrollo MySQL dhe provo përsëri.';
    }
}

$page = $pages[$pageKey];
$viewFile = __DIR__ . '/../src/Views/pages/' . $page['view'];

if (in_array($pageKey, ['login', 'register', 'verify-email'], true)) {
    require $viewFile;
    exit;
}

require __DIR__ . '/../src/Views/layout.php';
