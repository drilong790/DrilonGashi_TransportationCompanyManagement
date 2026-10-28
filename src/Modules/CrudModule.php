<?php

declare(strict_types=1);

namespace App\Modules;

use PDO;
use PDOException;

final class CrudModule
{
    private const MODULES = [
        'customers' => [
            'title' => 'Klientët',
            'singular' => 'klient',
            'table' => 'customers',
            'description' => 'Regjistrat e klientëve të kompanisë.',
            'columns' => [
                'company_name' => 'Kompania',
                'contact_person' => 'Personi kontaktues',
                'phone' => 'Telefoni',
                'email' => 'Email',
                'address' => 'Adresa',
            ],
            'list_sql' => 'SELECT id, company_name, contact_person, phone, email, address FROM customers ORDER BY id DESC LIMIT 250',
            'fields' => [
                'company_name' => ['label' => 'Emri i kompanisë', 'type' => 'text', 'required' => true, 'max' => 150],
                'contact_person' => ['label' => 'Personi kontaktues', 'type' => 'text', 'required' => true, 'max' => 150],
                'phone' => ['label' => 'Telefoni', 'type' => 'text', 'required' => true, 'max' => 30],
                'email' => ['label' => 'Email', 'type' => 'email', 'required' => true, 'max' => 150],
                'address' => ['label' => 'Adresa', 'type' => 'text', 'required' => true, 'max' => 255],
            ],
        ],
        'drivers' => [
            'title' => 'Shoferët',
            'singular' => 'shofer',
            'table' => 'drivers',
            'description' => 'Menaxho shoferët dhe statusin e tyre.',
            'columns' => [
                'full_name' => 'Emri i plotë',
                'license_number' => 'Numri i patentës',
                'phone' => 'Telefoni',
                'is_active_label' => 'Statusi',
            ],
            'list_sql' => "SELECT id, full_name, license_number, phone, IF(is_active = 1, 'Aktiv', 'Joaktiv') AS is_active_label FROM drivers ORDER BY id DESC LIMIT 250",
            'fields' => [
                'full_name' => ['label' => 'Emri i plotë', 'type' => 'text', 'required' => true, 'max' => 150],
                'license_number' => ['label' => 'Numri i patentës', 'type' => 'text', 'required' => true, 'max' => 50],
                'phone' => ['label' => 'Telefoni', 'type' => 'text', 'required' => true, 'max' => 30],
                'is_active' => [
                    'label' => 'Statusi',
                    'type' => 'select',
                    'required' => true,
                    'options' => ['1' => 'Aktiv', '0' => 'Joaktiv'],
                ],
            ],
        ],
        'vehicles' => [
            'title' => 'Automjetet',
            'singular' => 'automjet',
            'table' => 'vehicles',
            'description' => 'Menaxho automjetet, targat dhe kapacitetet.',
            'columns' => [
                'plate_number' => 'Targa',
                'vehicle_type' => 'Lloji',
                'capacity' => 'Kapaciteti',
                'is_active_label' => 'Statusi',
            ],
            'list_sql' => "SELECT id, plate_number, vehicle_type, capacity, IF(is_active = 1, 'Aktiv', 'Joaktiv') AS is_active_label FROM vehicles ORDER BY id DESC LIMIT 250",
            'fields' => [
                'plate_number' => ['label' => 'Targa', 'type' => 'text', 'required' => true, 'max' => 20],
                'vehicle_type' => ['label' => 'Lloji i automjetit', 'type' => 'text', 'required' => true, 'max' => 50],
                'capacity' => ['label' => 'Kapaciteti', 'type' => 'number', 'required' => true, 'min' => 1],
                'is_active' => [
                    'label' => 'Statusi',
                    'type' => 'select',
                    'required' => true,
                    'options' => ['1' => 'Aktiv', '0' => 'Joaktiv'],
                ],
            ],
        ],
        'transports' => [
            'title' => 'Transportet',
            'singular' => 'transport',
            'table' => 'transports',
            'description' => 'Planifiko, cakto dhe ndiq transportet.',
            'columns' => [
                'customer_name' => 'Klienti',
                'pickup_address' => 'Nisja',
                'destination_address' => 'Destinacioni',
                'scheduled_date' => 'Data e planifikuar',
                'status_label' => 'Statusi',
            ],
            'list_sql' => "SELECT t.id, c.company_name AS customer_name, t.pickup_address, t.destination_address, t.scheduled_date, CASE t.status WHEN 'PLANNED' THEN 'Planifikuar' WHEN 'IN_PROGRESS' THEN 'Në proces' WHEN 'COMPLETED' THEN 'Përfunduar' ELSE 'Anuluar' END AS status_label FROM transports t JOIN customers c ON c.id = t.customer_id ORDER BY t.scheduled_date DESC, t.id DESC LIMIT 250",
            'fields' => [
                'customer_id' => ['label' => 'Klienti', 'type' => 'select', 'required' => true, 'source' => 'customers'],
                'driver_id' => ['label' => 'Shoferi (opsional)', 'type' => 'select', 'required' => false, 'source' => 'drivers'],
                'vehicle_id' => ['label' => 'Automjeti (opsional)', 'type' => 'select', 'required' => false, 'source' => 'vehicles'],
                'created_by' => ['label' => 'Regjistruar nga', 'type' => 'select', 'required' => true, 'source' => 'users'],
                'pickup_address' => ['label' => 'Adresa e nisjes', 'type' => 'text', 'required' => true, 'max' => 255],
                'destination_address' => ['label' => 'Adresa e destinacionit', 'type' => 'text', 'required' => true, 'max' => 255],
                'cargo_description' => ['label' => 'Përshkrimi i ngarkesës', 'type' => 'textarea', 'required' => true],
                'scheduled_date' => ['label' => 'Data dhe ora', 'type' => 'datetime-local', 'required' => true],
                'status' => [
                    'label' => 'Statusi',
                    'type' => 'select',
                    'required' => true,
                    'options' => [
                        'PLANNED' => 'Planifikuar',
                        'IN_PROGRESS' => 'Në proces',
                        'COMPLETED' => 'Përfunduar',
                        'CANCELLED' => 'Anuluar',
                    ],
                ],
            ],
        ],
    ];

    public static function handle(PDO $pdo, string $key): array
    {
        $module = self::MODULES[$key];
        $action = $_GET['action'] ?? 'list';
        $id = self::positiveId($_GET['id'] ?? null);
        $errors = [];
        $formValues = [];
        $message = $_SESSION['flash_message'] ?? null;
        unset($_SESSION['flash_message']);

        if (!is_string($action) || !in_array($action, ['list', 'create', 'edit'], true)) {
            $action = 'list';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
            $postedId = self::positiveId($_POST['id'] ?? null);

            if (!self::validCsrfToken($_POST['csrf_token'] ?? null)) {
                http_response_code(400);
                $errors[] = 'Kërkesa nuk mund të verifikohej. Rifresko faqen dhe provo përsëri.';
                $action = in_array($action, ['create', 'update'], true) ? ($postedId === null ? 'create' : 'edit') : 'list';
                $id = $postedId;
            } elseif ($action === 'delete' && $postedId !== null) {
                try {
                    $statement = $pdo->prepare("DELETE FROM {$module['table']} WHERE id = :id");
                    $statement->execute(['id' => $postedId]);
                    $_SESSION['flash_message'] = $statement->rowCount() > 0
                        ? "{$module['title']} u fshi me sukses."
                        : 'Regjistri nuk u gjet ose është fshirë më parë.';
                    self::redirect($key);
                } catch (PDOException $exception) {
                    error_log("Deleting {$key} record failed: " . $exception->getMessage());
                    http_response_code(409);
                    $errors[] = $key === 'customers'
                        ? 'Klienti nuk mund të fshihet sepse ka transporte të lidhura me të.'
                        : 'Regjistri nuk mund të fshihet për shkak të lidhjeve në databazë.';
                    $action = 'list';
                }
            } elseif (in_array($action, ['create', 'update'], true) && ($action !== 'update' || $postedId !== null)) {
                $id = $postedId;
                $isUpdate = $action === 'update';
                $action = $isUpdate ? 'edit' : 'create';
                $existingRecord = null;
                if ($isUpdate) {
                    $existingStatement = $pdo->prepare("SELECT * FROM {$module['table']} WHERE id = :id");
                    $existingStatement->execute(['id' => $id]);
                    $existingRecord = $existingStatement->fetch();
                    if ($existingRecord === false) {
                        $existingRecord = null;
                        $action = 'list';
                        $errors[] = 'Regjistri nuk ekziston më dhe nuk mund të përditësohet.';
                    }
                }
                if ($action !== 'list') {
                    $formValues = self::submittedValues($module['fields']);
                    $errors = self::validate($pdo, $module['fields'], $formValues, $existingRecord);

                    if ($errors !== []) {
                        http_response_code(422);
                    } else {
                        try {
                            self::save($pdo, $module, $formValues, $action, $id);
                            $_SESSION['flash_message'] = $isUpdate
                                ? "{$module['title']} u përditësua me sukses."
                                : "{$module['title']} u shtua me sukses.";
                            self::redirect($key);
                        } catch (PDOException $exception) {
                            error_log("Saving {$key} record failed: " . $exception->getMessage());
                            http_response_code(422);
                            $errors[] = self::saveError($exception, $key);
                        }
                    }
                }
            } else {
                http_response_code(400);
                $errors[] = 'Veprimi i kërkuar nuk është i vlefshëm.';
                $action = 'list';
            }
        }

        $record = null;
        $rows = [];
        $options = [];

        if ($action === 'edit' && $id !== null) {
            $statement = $pdo->prepare("SELECT * FROM {$module['table']} WHERE id = :id");
            $statement->execute(['id' => $id]);
            $record = $statement->fetch();
            if ($record === false) {
                $record = null;
                $action = 'list';
                $message = 'Regjistri i kërkuar nuk u gjet.';
            } elseif ($formValues === []) {
                $formValues = self::formatRecord($module['fields'], $record);
            }
        } elseif ($action === 'edit') {
            $action = 'list';
            $message = 'ID e regjistrit nuk është e vlefshme.';
        }

        if (in_array($action, ['create', 'edit'], true)) {
            $options = self::fieldOptions($pdo, $module['fields'], $record);
        } else {
            $rows = $pdo->query($module['list_sql'])->fetchAll();
        }

        return [
            'module' => $module,
            'action' => $action,
            'id' => $id,
            'record' => $record,
            'rows' => $rows,
            'options' => $options,
            'errors' => $errors,
            'message' => $message,
            'values' => $formValues,
            'csrfToken' => self::csrfToken(),
        ];
    }

    private static function positiveId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private static function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private static function validCsrfToken(mixed $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    private static function submittedValues(array $fields): array
    {
        $values = [];
        foreach ($fields as $name => $field) {
            $value = $_POST[$name] ?? '';
            $values[$name] = is_string($value) ? trim($value) : '';
        }
        return $values;
    }

    private static function validate(PDO $pdo, array $fields, array &$values, ?array $record): array
    {
        $errors = [];
        $options = self::fieldOptions($pdo, $fields, $record);
        $normalizedDates = [];

        foreach ($fields as $name => $field) {
            $value = $values[$name] ?? '';
            if ($field['required'] && $value === '') {
                $errors[] = "Fusha \"{$field['label']}\" është e detyrueshme.";
                continue;
            }
            if ($value === '') {
                $values[$name] = null;
                continue;
            }
            if (isset($field['max']) && mb_strlen($value) > $field['max']) {
                $errors[] = "Fusha \"{$field['label']}\" nuk mund të ketë më shumë se {$field['max']} karaktere.";
            }
            if ($field['type'] === 'email' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = 'Shkruaj një adresë email të vlefshme.';
            }
            if ($field['type'] === 'number' && (!ctype_digit($value) || (int) $value < $field['min'])) {
                $errors[] = "Fusha \"{$field['label']}\" duhet të jetë numër i plotë dhe të jetë të paktën {$field['min']}.";
            }
            if ($field['type'] === 'select') {
                $validOptions = array_column($options[$name] ?? [], 'label', 'value');
                if (!array_key_exists($value, $validOptions)) {
                    $errors[] = "Zgjedhja për fushën \"{$field['label']}\" nuk është e vlefshme.";
                }
            }
            if ($field['type'] === 'datetime-local') {
                $date = \DateTime::createFromFormat('Y-m-d\TH:i', $value);
                if ($date === false || $date->format('Y-m-d\TH:i') !== $value) {
                    $errors[] = 'Data dhe ora e transportit nuk janë të vlefshme.';
                } else {
                    $normalizedDates[$name] = $date->format('Y-m-d H:i:s');
                }
            }
        }

        if ($errors === []) {
            foreach ($normalizedDates as $name => $date) {
                $values[$name] = $date;
            }
        }
        return $errors;
    }

    private static function fieldOptions(PDO $pdo, array $fields, ?array $record): array
    {
        $options = [];
        foreach ($fields as $name => $field) {
            if ($field['type'] !== 'select') {
                continue;
            }
            if (isset($field['options'])) {
                $options[$name] = [];
                foreach ($field['options'] as $value => $label) {
                    $options[$name][] = ['value' => (string) $value, 'label' => $label];
                }
            } elseif (isset($field['source'])) {
                $source = $field['source'];
                if ($source === 'customers') {
                    $statement = $pdo->query('SELECT id AS value, company_name AS label FROM customers ORDER BY company_name');
                } elseif ($source === 'drivers') {
                    $statement = $pdo->prepare(
                        'SELECT id AS value, full_name AS label FROM drivers WHERE is_active = 1 OR id = :selected ORDER BY full_name'
                    );
                    $statement->execute(['selected' => $record['driver_id'] ?? 0]);
                } elseif ($source === 'vehicles') {
                    $statement = $pdo->prepare(
                        "SELECT id AS value, CONCAT(plate_number, ' — ', vehicle_type) AS label FROM vehicles WHERE is_active = 1 OR id = :selected ORDER BY plate_number"
                    );
                    $statement->execute(['selected' => $record['vehicle_id'] ?? 0]);
                } else {
                    $statement = $pdo->prepare(
                        'SELECT id AS value, full_name AS label FROM users WHERE is_active = 1 OR id = :selected ORDER BY full_name'
                    );
                    $statement->execute(['selected' => $record['created_by'] ?? 0]);
                }
                $options[$name] = array_map(
                    static fn (array $row): array => ['value' => (string) $row['value'], 'label' => $row['label']],
                    $statement->fetchAll()
                );
            }
        }
        return $options;
    }

    private static function formatRecord(array $fields, array $record): array
    {
        $values = [];
        foreach ($fields as $name => $field) {
            $value = $record[$name] ?? '';
            if ($field['type'] === 'datetime-local' && $value !== '') {
                $value = str_replace(' ', 'T', substr((string) $value, 0, 16));
            }
            $values[$name] = (string) $value;
        }
        return $values;
    }

    private static function save(PDO $pdo, array $module, array $values, string $action, ?int $id): void
    {
        $columns = array_keys($module['fields']);
        if ($action === 'create') {
            $columnSql = implode(', ', $columns);
            $placeholderSql = implode(', ', array_map(static fn (string $column): string => ':' . $column, $columns));
            $statement = $pdo->prepare("INSERT INTO {$module['table']} ({$columnSql}) VALUES ({$placeholderSql})");
        } else {
            $assignments = implode(', ', array_map(static fn (string $column): string => "{$column} = :{$column}", $columns));
            $statement = $pdo->prepare("UPDATE {$module['table']} SET {$assignments} WHERE id = :id");
            $values['id'] = $id;
        }
        $statement->execute($values);
    }

    private static function saveError(PDOException $exception, string $key): string
    {
        if (($exception->errorInfo[1] ?? null) === 1062) {
            return match ($key) {
                'customers' => 'Ky email është përdorur tashmë nga një klient tjetër.',
                'drivers' => 'Ky numër patente është përdorur tashmë.',
                'vehicles' => 'Kjo targë është regjistruar tashmë.',
                default => 'Një vlerë unike e këtij regjistri ekziston tashmë.',
            };
        }
        return 'Të dhënat nuk u ruajtën. Kontrollo lidhjen me databazën dhe fushat e kërkuara.';
    }

    private static function redirect(string $key): void
    {
        header('Location: ?page=' . rawurlencode($key));
        exit;
    }
}
