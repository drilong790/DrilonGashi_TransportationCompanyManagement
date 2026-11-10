<?php

declare(strict_types=1);

use App\Auth\Authentication;
use App\Controllers\AccountController;
use App\Controllers\AuthController;
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
    'intro' => [
        'title' => 'Transporti, nën kontroll',
        'description' => 'Një pamje e qartë për menaxhimin e transportit.',
        'view' => 'intro.php',
    ],
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

$requestedPage = $_GET['page'] ?? 'intro';
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
$dashboardComparison = null;
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

if ($pageKey === 'intro') {
    require __DIR__ . '/../src/Views/pages/intro.php';
    exit;
}

try {
    $pdo = Connection::create();

    if (in_array($pageKey, ['login', 'register', 'verify-email'], true)) {
        $authState = AuthController::handle($pdo, $pageKey, $navigationItems);
        $user = $authState['user'];
        $loginError = $authState['loginError'];
        $registerError = $authState['registerError'];
        $registerValues = $authState['registerValues'];
        $verificationEmail = $authState['verificationEmail'];
        $verificationError = $authState['verificationError'];
        $verificationMessage = $authState['verificationMessage'];
    } else {
        $user = Authentication::currentUser($pdo);
        if ($user === null) {
            $_SESSION['redirect_after_login'] = $pageKey;
            header('Location: ?page=login');
            exit;
        }

        if (in_array($pageKey, ['add-admin', 'users'], true)) {
            $accountState = AccountController::handle($pdo, $pageKey, $user);
            $adminError = $accountState['adminError'];
            $adminValues = $accountState['adminValues'];
            $adminSuccess = $accountState['adminSuccess'];
            $accounts = $accountState['accounts'];
            $accountsError = $accountState['accountsError'];
            $accountsSuccess = $accountState['accountsSuccess'];
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
                $periodDays = (int) (new \DateTimeImmutable($dashboardReportFrom))
                    ->diff(new \DateTimeImmutable($dashboardReportTo))->days + 1;
                $comparisonTo = (new \DateTimeImmutable($dashboardReportFrom))
                    ->modify('-1 day')
                    ->format('Y-m-d');
                $comparisonFrom = (new \DateTimeImmutable($comparisonTo))
                    ->modify('-' . ($periodDays - 1) . ' days')
                    ->format('Y-m-d');
                $comparisonStatement = $pdo->prepare(
                    'SELECT status, COUNT(*) AS total
                    FROM transports
                    WHERE scheduled_date >= :date_from
                        AND scheduled_date < DATE_ADD(:date_to, INTERVAL 1 DAY)
                    GROUP BY status'
                );
                $comparisonStatement->execute([
                    'date_from' => $comparisonFrom . ' 00:00:00',
                    'date_to' => $comparisonTo,
                ]);
                $comparisonCounts = array_column($comparisonStatement->fetchAll(), 'total', 'status');
                $comparisonTotal = array_sum(array_map('intval', $comparisonCounts));
                $currentTotal = array_sum(array_map('intval', $statusCounts));
                $dashboardComparison = [
                    'from' => $comparisonFrom,
                    'to' => $comparisonTo,
                    'total' => $comparisonTotal,
                    'change' => $currentTotal - $comparisonTotal,
                    'change_percent' => $comparisonTotal > 0
                        ? round((($currentTotal - $comparisonTotal) / $comparisonTotal) * 100, 1)
                        : null,
                ];
                $reportLabels = [
                    'PLANNED' => 'Planifikuar',
                    'IN_PROGRESS' => 'Në proces',
                    'COMPLETED' => 'Përfunduar',
                    'CANCELLED' => 'Anuluar',
                ];
                foreach ($reportLabels as $status => $label) {
                    $total = (int) ($statusCounts[$status] ?? 0);
                    $dashboardReport[] = [
                        'status' => $status,
                        'label' => $label,
                        'total' => $total,
                        'comparison_total' => (int) ($comparisonCounts[$status] ?? 0),
                    ];
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
