<?php

declare(strict_types=1);

$pages = [
    'dashboard' => [
        'title' => 'Paneli kryesor',
        'description' => 'Përmbledhje e moduleve të menaxhimit të transportit.',
        'view' => 'dashboard.php',
    ],
    'transports' => [
        'title' => 'Transportet',
        'description' => 'Planifiko dhe ndiq transportet e kompanisë.',
        'view' => 'module-placeholder.php',
        'module_description' => 'Këtu do të shfaqet lista e transporteve dhe veprimet për krijimin ose përditësimin e tyre.',
        'next_step' => 'Lidhja me tabelën transports dhe krijimi i flow-t të listimit.',
    ],
    'customers' => [
        'title' => 'Klientët',
        'description' => 'Menaxho informacionin e klientëve të kompanisë.',
        'view' => 'module-placeholder.php',
        'module_description' => 'Këtu do të shfaqet lista e klientëve dhe formulari për shtimin ose përditësimin e tyre.',
        'next_step' => 'Lidhja me tabelën customers dhe krijimi i flow-t të menaxhimit.',
    ],
    'drivers' => [
        'title' => 'Shoferët',
        'description' => 'Menaxho shoferët dhe statusin e tyre.',
        'view' => 'module-placeholder.php',
        'module_description' => 'Këtu do të shfaqet lista e shoferëve dhe statusi i tyre aktiv.',
        'next_step' => 'Lidhja me tabelën drivers dhe krijimi i flow-t të listimit.',
    ],
    'vehicles' => [
        'title' => 'Automjetet',
        'description' => 'Menaxho automjetet dhe kapacitetet e tyre.',
        'view' => 'module-placeholder.php',
        'module_description' => 'Këtu do të shfaqet lista e automjeteve, targat dhe kapacitetet.',
        'next_step' => 'Lidhja me tabelën vehicles dhe krijimi i flow-t të listimit.',
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
$viewFile = __DIR__ . '/../src/Views/pages/' . $page['view'];

require __DIR__ . '/../src/Views/layout.php';
