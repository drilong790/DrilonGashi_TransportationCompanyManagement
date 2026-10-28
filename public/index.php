<?php

declare(strict_types=1);

use App\Database\Connection;
use App\Modules\CrudModule;

require_once __DIR__ . '/../src/Database/Connection.php';
require_once __DIR__ . '/../src/Modules/CrudModule.php';

$dashboardStats = null;
$databaseError = null;
$moduleData = null;

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

$pageKey = $_GET['page'] ?? 'dashboard';

if (!is_string($pageKey) || !isset($pages[$pageKey])) {
    http_response_code(404);
    $pageKey = 'not_found';
}

$page = $pages[$pageKey];

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (in_array($pageKey, ['customers', 'drivers', 'vehicles', 'transports'], true)) {
    try {
        $pdo = Connection::create();
        $moduleData = CrudModule::handle($pdo, $pageKey);
    } catch (PDOException $exception) {
        error_log("Database request failed for {$pageKey}: " . $exception->getMessage());
        http_response_code(503);
        $databaseError = 'Nuk mund të kryhet veprimi për shkak të lidhjes me databazën. Kontrollo që MySQL është aktiv dhe databaza është konfiguruar.';
    } catch (Throwable $exception) {
        error_log("Application request failed for {$pageKey}: " . $exception->getMessage());
        http_response_code(500);
        $databaseError = 'Ndodhi një gabim gjatë përgatitjes së modulit. Provo përsëri ose kontrollo log-un e PHP-së.';
    }
} elseif ($pageKey === 'dashboard') {
    try {
        $pdo = Connection::create();
        $statement = $pdo->query(
            'SELECT
                (SELECT COUNT(*) FROM transports) AS transports,
                (SELECT COUNT(*) FROM customers) AS customers,
                (SELECT COUNT(*) FROM drivers) AS drivers,
                (SELECT COUNT(*) FROM vehicles) AS vehicles'
        );
        $dashboardStats = $statement->fetch();
    } catch (PDOException $exception) {
        error_log('Dashboard database query failed: ' . $exception->getMessage());
        http_response_code(503);
        $databaseError = 'Nuk mund të lexohen të dhënat nga databaza. Kontrollo që MySQL është aktiv dhe databaza transport_management është importuar.';
    }
}

$viewFile = __DIR__ . '/../src/Views/pages/' . $page['view'];

require __DIR__ . '/../src/Views/layout.php';
