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
                    $allowedDestinations = array_merge(array_keys($navigationItems), ['not_found']);
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
            if ($user['role'] !== 'ADMIN') {
                http_response_code(403);
                $adminError = 'Vetëm administratori mund të krijojë llogari administratori.';
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
            if ($user['role'] !== 'ADMIN') {
                http_response_code(403);
                $accountsError = 'Vetëm administratori mund të menaxhojë llogaritë.';
            } else {
                $accountsSuccess = $_SESSION['flash_message'] ?? null;
                unset($_SESSION['flash_message']);

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $accountId = filter_var(
                        $_POST['id'] ?? null,
                        FILTER_VALIDATE_INT,
                        ['options' => ['min_range' => 1]]
                    );
                    if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
                        http_response_code(400);
                        $accountsError = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
                    } elseif ($accountId === false) {
                        http_response_code(422);
                        $accountsError = 'Llogaria e zgjedhur nuk është e vlefshme.';
                    } elseif ((int) $accountId === (int) $user['id']) {
                        http_response_code(422);
                        $accountsError = 'Nuk mund ta fshish llogarinë me të cilën je kyçur.';
                    } else {
                        $accountStatement = $pdo->prepare(
                            'SELECT id, full_name FROM users WHERE id = :id LIMIT 1'
                        );
                        $accountStatement->execute(['id' => $accountId]);
                        $accountToDelete = $accountStatement->fetch();
                        if ($accountToDelete === false) {
                            http_response_code(404);
                            $accountsError = 'Llogaria nuk u gjet; lista mund të jetë përditësuar.';
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
                                        'DELETE FROM users WHERE id = :id AND id <> :current_user_id'
                                    );
                                    $deleteStatement->execute([
                                        'id' => $accountId,
                                        'current_user_id' => (int) $user['id'],
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

                $accounts = $pdo->query(
                    'SELECT u.id, u.full_name, u.email, u.role, u.is_active, u.email_verified_at,
                        u.created_at, (SELECT COUNT(*) FROM transports t WHERE t.created_by = u.id) AS transport_count
                    FROM users u
                    ORDER BY u.role, u.full_name, u.id'
                )->fetchAll();
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
