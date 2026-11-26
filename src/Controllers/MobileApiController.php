<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Authentication;
use App\Modules\CrudModule;
use PDO;
use PDOException;
use Throwable;

final class MobileApiController
{
    private const TOKEN_LIFETIME_DAYS = 30;
    private const TRANSPORT_STATUSES = ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'];

    public static function handle(PDO $pdo): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');

        try {
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $action = $_GET['action'] ?? '';
            if (!is_string($action)) {
                self::respond(400, ['error' => 'Veprimi nuk është i vlefshëm.']);
            }

            if ($action === 'login' && $method === 'POST') {
                self::login($pdo);
            }

            $token = self::bearerToken();
            $user = $token === null ? null : self::userForToken($pdo, $token);
            if ($user === null) {
                self::respond(401, ['error' => 'Hyr përsëri për të vazhduar.']);
            }

            if ($action === 'logout' && $method === 'POST') {
                $statement = $pdo->prepare('DELETE FROM mobile_api_tokens WHERE token_hash = :token_hash');
                $statement->execute(['token_hash' => hash('sha256', $token)]);
                self::respond(200, ['data' => ['message' => 'Dole me sukses.']]);
            }

            if ($action === 'me' && $method === 'GET') {
                self::respond(200, ['data' => $user]);
            }

            if ($action === 'resources' && $method === 'GET') {
                if ($user['role'] === 'DRIVER') {
                    self::respond(403, ['error' => 'Shoferët nuk mund të përdorin burimet e menaxhimit.']);
                }
                self::resources($pdo);
            }

            if ($action === 'transports' && $method === 'GET') {
                self::transports($pdo, $user);
            }

            if ($action === 'dashboard' && $method === 'GET') {
                self::dashboard($pdo, $user);
            }

            if ($action === 'driver-status' && $method === 'POST') {
                self::updateDriverStatus($pdo, $user);
            }

            if ($action === 'transport' && $method === 'GET') {
                $id = self::positiveId($_GET['id'] ?? null);
                if ($id === null) {
                    self::respond(422, ['error' => 'ID e transportit nuk është e vlefshme.']);
                }
                $transport = self::transportById($pdo, $id, $user);
                self::respond($transport === null ? 404 : 200, $transport === null
                    ? ['error' => 'Transporti nuk u gjet.']
                    : ['data' => $transport]);
            }

            if (in_array($method, ['POST', 'PUT'], true) && $action === 'transport') {
                self::saveTransport($pdo, $user, $method);
            }

            self::respond(404, ['error' => 'Adresa e API-së nuk u gjet.']);
        } catch (PDOException $exception) {
            error_log('Mobile API database request failed: ' . $exception->getMessage());
            self::respond(503, ['error' => 'Shërbimi nuk mund të lidhet me databazën.']);
        } catch (Throwable $exception) {
            error_log('Mobile API request failed: ' . $exception->getMessage());
            self::respond(500, ['error' => 'Kërkesa nuk mund të përfundonte.']);
        }
    }

    private static function login(PDO $pdo): void
    {
        $body = self::jsonBody();
        $email = $body['email'] ?? null;
        $password = $body['password'] ?? null;
        if (!is_string($email) || !is_string($password) || $email === '' || $password === '') {
            self::respond(422, ['error' => 'Shkruaj email-in dhe fjalëkalimin.']);
        }

        $user = Authentication::attemptMobile($pdo, trim($email), $password);
        if ($user === null) {
            self::respond(401, ['error' => 'Email-i ose fjalëkalimi nuk është i saktë.']);
        }

        $token = bin2hex(random_bytes(32));
        $statement = $pdo->prepare(
            'INSERT INTO mobile_api_tokens (user_id, token_hash, expires_at)
            VALUES (:user_id, :token_hash, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL '
                . self::TOKEN_LIFETIME_DAYS . ' DAY))'
        );
        $statement->execute([
            'user_id' => (int) $user['id'],
            'token_hash' => hash('sha256', $token),
        ]);

        self::respond(200, [
            'data' => [
                'token' => $token,
                'expires_in' => self::TOKEN_LIFETIME_DAYS * 86400,
                'user' => $user,
            ],
        ]);
    }

    private static function resources(PDO $pdo): void
    {
        self::respond(200, [
            'data' => [
                'customers' => $pdo->query(
                    'SELECT id, company_name FROM customers ORDER BY company_name'
                )->fetchAll(),
                'drivers' => $pdo->query(
                    'SELECT id, full_name FROM drivers WHERE is_active = 1 ORDER BY full_name'
                )->fetchAll(),
                'vehicles' => $pdo->query(
                    'SELECT id, plate_number, vehicle_type
                    FROM vehicles WHERE is_active = 1 ORDER BY plate_number'
                )->fetchAll(),
            ],
        ]);
    }

    private static function dashboard(PDO $pdo, array $user): void
    {
        $driverOnly = $user['role'] === 'DRIVER';
        $where = $driverOnly ? ' WHERE driver_id = :driver_id' : '';
        $parameters = $driverOnly ? ['driver_id' => (int) $user['driver_id']] : [];
        $statement = $pdo->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(status = 'PLANNED') AS planned,
                SUM(status = 'IN_PROGRESS') AS in_progress,
                SUM(status = 'COMPLETED') AS completed,
                SUM(status = 'CANCELLED') AS cancelled,
                SUM(DATE(scheduled_date) = CURRENT_DATE AND status <> 'CANCELLED') AS today
            FROM transports{$where}"
        );
        $statement->execute($parameters);
        $counts = $statement->fetch();
        foreach (['total', 'planned', 'in_progress', 'completed', 'cancelled', 'today'] as $field) {
            $counts[$field] = (int) ($counts[$field] ?? 0);
        }
        self::respond(200, ['data' => $counts]);
    }

    private static function updateDriverStatus(PDO $pdo, array $user): void
    {
        if ($user['role'] !== 'DRIVER' || $user['driver_id'] === null) {
            self::respond(403, ['error' => 'Vetëm shoferi i caktuar mund të ndryshojë statusin.']);
        }

        $body = self::jsonBody();
        $id = self::positiveId($body['transport_id'] ?? null);
        $status = $body['status'] ?? null;
        if ($id === null || !is_string($status) || !in_array($status, ['IN_PROGRESS', 'COMPLETED'], true)) {
            self::respond(422, ['error' => 'Transporti ose statusi nuk është i vlefshëm.']);
        }
        $previousStatus = $status === 'IN_PROGRESS' ? 'PLANNED' : 'IN_PROGRESS';
        $statement = $pdo->prepare(
            'UPDATE transports
            SET status = :status
            WHERE id = :id AND driver_id = :driver_id AND status = :previous_status'
        );
        $statement->execute([
            'status' => $status,
            'id' => $id,
            'driver_id' => (int) $user['driver_id'],
            'previous_status' => $previousStatus,
        ]);
        if ($statement->rowCount() !== 1) {
            $transport = self::transportById($pdo, $id, $user);
            if ($transport === null) {
                self::respond(404, ['error' => 'Transporti nuk u gjet ose nuk të është caktuar.']);
            }
            self::respond(409, ['error' => 'Statusi i transportit ka ndryshuar. Rifresko listën dhe provo përsëri.']);
        }
        self::respond(200, ['data' => self::transportById($pdo, $id, $user)]);
    }

    private static function transports(PDO $pdo, array $user): void
    {
        $filters = [];
        foreach (['search', 'status', 'date_from', 'date_to', 'customer_id', 'driver_id'] as $name) {
            $value = $_GET[$name] ?? '';
            if (!is_string($value)) {
                self::respond(422, ['error' => 'Filtri të pavlefshëm.']);
            }
            $filters[$name] = trim($value);
        }
        if ($filters['status'] !== '' && !in_array($filters['status'], self::TRANSPORT_STATUSES, true)) {
            self::respond(422, ['error' => 'Filtri i statusit nuk është i vlefshëm.']);
        }
        if (mb_strlen($filters['search']) > 100) {
            self::respond(422, ['error' => 'Kërkimi nuk mund të jetë më i gjatë se 100 karaktere.']);
        }
        foreach (['date_from', 'date_to'] as $dateField) {
            if ($filters[$dateField] !== '' && !self::validDate($filters[$dateField])) {
                self::respond(422, ['error' => 'Datat duhet të jenë në formatin YYYY-MM-DD.']);
            }
        }
        if (
            $filters['date_from'] !== ''
            && $filters['date_to'] !== ''
            && $filters['date_from'] > $filters['date_to']
        ) {
            self::respond(422, ['error' => 'Data e fillimit nuk mund të jetë pas mbarimit.']);
        }

        $where = [];
        $parameters = [];
        if ($user['role'] === 'DRIVER') {
            if ($user['driver_id'] === null) {
                self::respond(403, ['error' => 'Llogaria nuk është e lidhur me një shofer.']);
            }
            $where[] = 't.driver_id = :session_driver_id';
            $parameters['session_driver_id'] = (int) $user['driver_id'];
        }
        if ($filters['search'] !== '') {
            $where[] = '(c.company_name LIKE :search_customer OR t.pickup_address LIKE :search_pickup '
                . 'OR t.destination_address LIKE :search_destination OR t.cargo_description LIKE :search_cargo '
                . 'OR d.full_name LIKE :search_driver OR v.plate_number LIKE :search_plate)';
            foreach (['customer', 'pickup', 'destination', 'cargo', 'driver', 'plate'] as $field) {
                $parameters['search_' . $field] = '%' . $filters['search'] . '%';
            }
        }
        if ($filters['status'] !== '') {
            $where[] = 't.status = :status';
            $parameters['status'] = $filters['status'];
        }
        if ($filters['date_from'] !== '') {
            $where[] = 't.scheduled_date >= :date_from';
            $parameters['date_from'] = $filters['date_from'] . ' 00:00:00';
        }
        if ($filters['date_to'] !== '') {
            $where[] = 't.scheduled_date < DATE_ADD(:date_to, INTERVAL 1 DAY)';
            $parameters['date_to'] = $filters['date_to'];
        }
        foreach ([
            'customer_id' => ['column' => 't.customer_id', 'table' => 'customers'],
            'driver_id' => ['column' => 't.driver_id', 'table' => 'drivers'],
        ] as $name => $filter) {
            if ($filters[$name] === '') {
                continue;
            }
            $id = self::positiveId($filters[$name]);
            if ($id === null) {
                self::respond(422, ['error' => 'Filtri të pavlefshëm.']);
            }
            $exists = $pdo->prepare('SELECT COUNT(*) FROM ' . $filter['table'] . ' WHERE id = :id');
            $exists->execute(['id' => $id]);
            if ((int) $exists->fetchColumn() === 0) {
                self::respond(422, ['error' => 'Klienti ose shoferi i zgjedhur nuk u gjet.']);
            }
            $where[] = $filter['column'] . ' = :' . $name;
            $parameters[$name] = $id;
        }

        $whereSql = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $statement = $pdo->prepare(
            "SELECT t.id, t.customer_id, t.driver_id, t.vehicle_id,
                c.company_name AS customer_name, d.full_name AS driver_name,
                v.plate_number AS vehicle_plate, t.pickup_address, t.destination_address,
                t.cargo_description, t.scheduled_date, t.scheduled_end, t.status,
                u.full_name AS created_by_name
            FROM transports t
            JOIN customers c ON c.id = t.customer_id
            LEFT JOIN drivers d ON d.id = t.driver_id
            LEFT JOIN vehicles v ON v.id = t.vehicle_id
            JOIN users u ON u.id = t.created_by
            {$whereSql}
            ORDER BY t.scheduled_date DESC, t.id DESC
            LIMIT 250"
        );
        $statement->execute($parameters);
        self::respond(200, ['data' => $statement->fetchAll()]);
    }

    private static function saveTransport(PDO $pdo, array $user, string $method): void
    {
        if (!in_array($user['role'], ['SUPERADMIN', 'ADMIN', 'DISPATCHER'], true)) {
            self::respond(403, ['error' => 'Nuk ke leje për të ndryshuar transportet.']);
        }

        $id = $method === 'PUT' ? self::positiveId($_GET['id'] ?? null) : null;
        if ($method === 'PUT' && $id === null) {
            self::respond(422, ['error' => 'ID e transportit nuk është e vlefshme.']);
        }
        $values = self::transportValues(self::jsonBody());
        if (isset($values['error'])) {
            self::respond(422, ['error' => $values['error']]);
        }

        $pdo->beginTransaction();
        try {
            $previous = null;
            if ($id !== null) {
                $existing = $pdo->prepare(
                    'SELECT driver_id, vehicle_id, status FROM transports WHERE id = :id FOR UPDATE'
                );
                $existing->execute(['id' => $id]);
                $previous = $existing->fetch();
                if ($previous === false) {
                    $pdo->rollBack();
                    self::respond(404, ['error' => 'Transporti nuk u gjet.']);
                }
            }

            self::validateTransportOptions($pdo, $values, $previous);
            if ($values['scheduled_end'] <= $values['scheduled_date']) {
                $pdo->rollBack();
                self::respond(422, ['error' => 'Përfundimi duhet të jetë pas fillimit.']);
            }
            $conflicts = self::transportConflicts($pdo, $values, $id ?? 0);
            if ($conflicts !== []) {
                $pdo->rollBack();
                self::respond(422, ['error' => implode(' ', $conflicts)]);
            }

            if ($id === null) {
                $statement = $pdo->prepare(
                    'INSERT INTO transports
                    (customer_id, driver_id, vehicle_id, created_by, pickup_address, destination_address,
                        cargo_description, scheduled_date, scheduled_end, status)
                    VALUES (:customer_id, :driver_id, :vehicle_id, :created_by, :pickup_address,
                        :destination_address, :cargo_description, :scheduled_date, :scheduled_end, :status)'
                );
                $statement->execute($values + ['created_by' => (int) $user['id']]);
                $id = (int) $pdo->lastInsertId();
            } else {
                $statement = $pdo->prepare(
                    'UPDATE transports SET customer_id = :customer_id, driver_id = :driver_id,
                        vehicle_id = :vehicle_id, pickup_address = :pickup_address,
                        destination_address = :destination_address, cargo_description = :cargo_description,
                        scheduled_date = :scheduled_date, scheduled_end = :scheduled_end, status = :status
                    WHERE id = :id'
                );
                $statement->execute($values + ['id' => $id]);
            }
            $pdo->commit();
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (($exception->errorInfo[1] ?? null) === 1452) {
                self::respond(422, ['error' => 'Klienti, shoferi ose automjeti i zgjedhur nuk ekziston.']);
            }
            throw $exception;
        }

        $warnings = [];
        try {
            $warnings = CrudModule::notifyTransportChanges($pdo, $id, $previous);
        } catch (PDOException $exception) {
            error_log("Mobile API transport {$id} notification lookup failed: " . $exception->getMessage());
            $warnings[] = 'Njoftimet me email nuk mund të përgatiteshin.';
        }
        $transport = self::transportById($pdo, $id);
        self::respond($method === 'POST' ? 201 : 200, [
            'data' => $transport,
            'warnings' => $warnings,
        ]);
    }

    private static function transportValues(array $body): array
    {
        $stringFields = [
            'pickup_address' => 255,
            'destination_address' => 255,
            'cargo_description' => 10000,
        ];
        $values = [];
        foreach ($stringFields as $field => $maxLength) {
            $value = $body[$field] ?? null;
            if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $maxLength) {
                return ['error' => 'Plotëso saktë adresat dhe përshkrimin e ngarkesës.'];
            }
            $values[$field] = trim($value);
        }

        $customerId = self::positiveId($body['customer_id'] ?? null);
        if ($customerId === null) {
            return ['error' => 'Zgjidh një klient të vlefshëm.'];
        }
        $values['customer_id'] = $customerId;

        foreach (['driver_id', 'vehicle_id'] as $field) {
            $value = $body[$field] ?? null;
            $values[$field] = $value === null || $value === '' ? null : self::positiveId($value);
            if ($value !== null && $value !== '' && $values[$field] === null) {
                return ['error' => 'Zgjedhja e shoferit ose automjetit nuk është e vlefshme.'];
            }
        }

        foreach (['scheduled_date', 'scheduled_end'] as $field) {
            $value = $body[$field] ?? null;
            if (!is_string($value) || ($date = self::parseDateTime($value)) === null) {
                return ['error' => 'Data dhe ora e transportit nuk janë të vlefshme.'];
            }
            $values[$field] = $date;
        }

        $status = $body['status'] ?? 'PLANNED';
        if (!is_string($status) || !in_array($status, self::TRANSPORT_STATUSES, true)) {
            return ['error' => 'Statusi i transportit nuk është i vlefshëm.'];
        }
        $values['status'] = $status;
        return $values;
    }

    private static function validateTransportOptions(PDO $pdo, array $values, ?array $previous): void
    {
        $customer = $pdo->prepare('SELECT id FROM customers WHERE id = :id FOR UPDATE');
        $customer->execute(['id' => $values['customer_id']]);
        if ($customer->fetchColumn() === false) {
            $pdo->rollBack();
            self::respond(422, ['error' => 'Klienti i zgjedhur nuk u gjet.']);
        }

        foreach (['driver_id' => 'drivers', 'vehicle_id' => 'vehicles'] as $field => $table) {
            if ($values[$field] === null) {
                continue;
            }
            $selectedPreviously = $previous !== null
                && (int) ($previous[$field] ?? 0) === $values[$field];
            $sql = $selectedPreviously
                ? "SELECT id FROM {$table} WHERE id = :id FOR UPDATE"
                : "SELECT id FROM {$table} WHERE id = :id AND is_active = 1 FOR UPDATE";
            $statement = $pdo->prepare($sql);
            $statement->execute(['id' => $values[$field]]);
            if ($statement->fetchColumn() === false) {
                $pdo->rollBack();
                self::respond(422, ['error' => 'Shoferi ose automjeti i zgjedhur nuk është aktiv.']);
            }
        }
    }

    private static function transportConflicts(PDO $pdo, array $values, int $excludeId): array
    {
        if ($values['status'] === 'CANCELLED') {
            return [];
        }
        $errors = [];
        foreach (['driver_id' => 'shoferi', 'vehicle_id' => 'automjeti'] as $field => $label) {
            if ($values[$field] === null) {
                continue;
            }
            $statement = $pdo->prepare(
                "SELECT COUNT(*) FROM transports
                WHERE {$field} = :resource_id AND status <> 'CANCELLED'
                    AND scheduled_date < :scheduled_end AND scheduled_end > :scheduled_date
                    AND id <> :exclude_id"
            );
            $statement->execute([
                'resource_id' => $values[$field],
                'scheduled_end' => $values['scheduled_end'],
                'scheduled_date' => $values['scheduled_date'],
                'exclude_id' => $excludeId,
            ]);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[] = "Ky {$label} është i caktuar në një transport tjetër gjatë këtij orari.";
            }
        }
        return $errors;
    }

    private static function transportById(PDO $pdo, int $id, ?array $user = null): ?array
    {
        $driverFilter = $user !== null && $user['role'] === 'DRIVER'
            ? ' AND t.driver_id = :driver_id'
            : '';
        $statement = $pdo->prepare(
            'SELECT t.id, t.customer_id, c.company_name AS customer_name, t.driver_id,
                d.full_name AS driver_name, t.vehicle_id, v.plate_number AS vehicle_plate,
                t.pickup_address, t.destination_address, t.cargo_description, t.scheduled_date,
                t.scheduled_end, t.status, u.full_name AS created_by_name
            FROM transports t
            JOIN customers c ON c.id = t.customer_id
            LEFT JOIN drivers d ON d.id = t.driver_id
            LEFT JOIN vehicles v ON v.id = t.vehicle_id
            JOIN users u ON u.id = t.created_by
            WHERE t.id = :id' . $driverFilter . ' LIMIT 1'
        );
        $parameters = ['id' => $id];
        if ($driverFilter !== '') {
            $parameters['driver_id'] = (int) $user['driver_id'];
        }
        $statement->execute($parameters);
        $transport = $statement->fetch();
        return $transport === false ? null : $transport;
    }

    private static function userForToken(PDO $pdo, string $token): ?array
    {
        $statement = $pdo->prepare(
            "SELECT u.id, u.full_name, u.email, u.role, u.driver_id, t.token_hash
            FROM mobile_api_tokens t
            JOIN users u ON u.id = t.user_id
            LEFT JOIN drivers d ON d.id = u.driver_id
            WHERE t.token_hash = :token_hash AND t.expires_at > CURRENT_TIMESTAMP
                AND u.is_active = 1 AND u.email_verified_at IS NOT NULL
                AND u.role IN ('SUPERADMIN', 'ADMIN', 'DISPATCHER', 'DRIVER')
                AND (u.role <> 'DRIVER' OR (u.driver_id IS NOT NULL AND d.is_active = 1))
            LIMIT 1"
        );
        $statement->execute(['token_hash' => hash('sha256', $token)]);
        $user = $statement->fetch();
        if ($user === false) {
            return null;
        }
        $update = $pdo->prepare(
            'UPDATE mobile_api_tokens SET last_used_at = CURRENT_TIMESTAMP WHERE token_hash = :token_hash'
        );
        $update->execute(['token_hash' => $user['token_hash']]);
        unset($user['token_hash']);
        return $user;
    }

    private static function bearerToken(): ?string
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ((!is_string($authorization) || $authorization === '') && function_exists('getallheaders')) {
            $headers = getallheaders();
            $authorization = is_array($headers) ? ($headers['Authorization'] ?? '') : '';
        }
        if (!is_string($authorization) || preg_match('/^Bearer ([a-f0-9]{64})$/iD', $authorization, $matches) !== 1) {
            return null;
        }
        return strtolower($matches[1]);
    }

    private static function jsonBody(): array
    {
        $body = file_get_contents('php://input');
        $decoded = json_decode($body === false ? '' : $body, true);
        if (!is_array($decoded)) {
            self::respond(400, ['error' => 'Trupi i kërkesës duhet të jetë JSON i vlefshëm.']);
        }
        return $decoded;
    }

    private static function positiveId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private static function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function parseDateTime(string $value): ?string
    {
        foreach (['Y-m-d\TH:i', 'Y-m-d\TH:i:s', 'Y-m-d H:i:s'] as $format) {
            $date = \DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        return null;
    }

    private static function respond(int $status, array $payload): void
    {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
