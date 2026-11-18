<?php

declare(strict_types=1);

use App\Controllers\MobileApiController;
use App\Database\Connection;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    MobileApiController::handle(Connection::create());
} catch (PDOException $exception) {
    error_log('Mobile API database connection failed: ' . $exception->getMessage());
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    http_response_code(503);
    echo json_encode(['error' => 'Shërbimi nuk mund të lidhet me databazën.'], JSON_UNESCAPED_UNICODE);
}
