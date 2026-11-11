<?php

declare(strict_types=1);

final class TestBrowser
{
    private ?string $sessionCookie = null;

    public function __construct(private string $baseUrl)
    {
    }

    public function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    public function post(string $path, array $fields): array
    {
        return $this->request('POST', $path, $fields);
    }

    public function csrfToken(string $html): string
    {
        if (preg_match('/name="csrf_token" value="([^"]+)"/', $html, $matches) !== 1) {
            throw new RuntimeException('The page did not contain a CSRF token.');
        }

        return html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8');
    }

    public function login(string $email, string $password): array
    {
        $page = $this->get('/?page=login');
        return $this->post('/?page=login', [
            'csrf_token' => $this->csrfToken($page['body']),
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function request(string $method, string $path, array $fields = []): array
    {
        $headers = ['Connection: close'];
        $content = '';
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $content = http_build_query($fields);
        }
        if ($this->sessionCookie !== null) {
            $headers[] = 'Cookie: ' . $this->sessionCookie;
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $content,
                'ignore_errors' => true,
                'follow_location' => 0,
                'timeout' => 5,
            ],
        ]);
        $http_response_header = [];
        $body = file_get_contents($this->baseUrl . $path, false, $context);
        if ($body === false) {
            throw new RuntimeException("HTTP request failed: {$method} {$path}");
        }

        $status = null;
        $responseHeaders = [];
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $header, $matches) === 1) {
                $status = (int) $matches[1];
            } elseif (stripos($header, 'Set-Cookie: PHPSESSID=') === 0) {
                $this->sessionCookie = explode(';', substr($header, strlen('Set-Cookie: ')), 2)[0];
            } else {
                $responseHeaders[] = $header;
            }
        }

        if ($status === null) {
            throw new RuntimeException("HTTP response had no status: {$method} {$path}");
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => $body,
        ];
    }
}

function expectStatus(array $response, int $expected, string $description): void
{
    if ($response['status'] !== $expected) {
        throw new RuntimeException(
            "{$description}: expected HTTP {$expected}, received {$response['status']}."
        );
    }
    echo "PASS {$description}\n";
}

function expectContains(string $haystack, string $needle, string $description): void
{
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException("{$description}: expected text was not present.");
    }
    echo "PASS {$description}\n";
}

function expectNotContains(string $haystack, string $needle, string $description): void
{
    if (str_contains($haystack, $needle)) {
        throw new RuntimeException("{$description}: unexpected text was present.");
    }
    echo "PASS {$description}\n";
}

function expectTrue(bool $condition, string $description): void
{
    if (!$condition) {
        throw new RuntimeException($description);
    }
    echo "PASS {$description}\n";
}

function expectLocation(array $response, string $destination, string $description): void
{
    foreach ($response['headers'] as $header) {
        if (stripos($header, 'Location: ') === 0 && str_ends_with($header, $destination)) {
            echo "PASS {$description}\n";
            return;
        }
    }

    throw new RuntimeException("{$description}: redirect to {$destination} was not returned.");
}

function createTestUser(PDO $pdo, string $name, string $email, string $password, string $role): int
{
    $statement = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at)
        VALUES (:full_name, :email, :password_hash, :role, 1, CURRENT_TIMESTAMP)'
    );
    $statement->execute([
        'full_name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);

    return (int) $pdo->lastInsertId();
}

function createDatabaseConnection(string $host, string $port, string $database): PDO
{
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD');

    return new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password === false ? '' : $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
}

$serverProcess = null;
$testDatabaseName = null;
$testDatabaseCreated = false;
$testExitCode = 0;

try {
    if (!extension_loaded('pdo_mysql')) {
        throw new RuntimeException('The pdo_mysql PHP extension is required.');
    }

    $dbHost = getenv('DB_HOST') ?: '127.0.0.1';
    $dbPort = getenv('DB_PORT') ?: '3306';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASSWORD');
    $serverConnection = new PDO(
        "mysql:host={$dbHost};port={$dbPort};charset=utf8mb4",
        $username,
        $password === false ? '' : $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $testDatabaseName = 'transport_management_test_' . bin2hex(random_bytes(6));
    $serverConnection->exec(
        "CREATE DATABASE `{$testDatabaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
    );
    $testDatabaseCreated = true;
    $pdo = createDatabaseConnection($dbHost, $dbPort, $testDatabaseName);

    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Could not read database/schema.sql.');
    }
    $schema = preg_replace(
        '/\ACREATE DATABASE IF NOT EXISTS transport_management\s+'
        . 'CHARACTER SET utf8mb4\s+COLLATE utf8mb4_unicode_ci;\s*'
        . 'USE transport_management;\s*/',
        '',
        $schema
    );
    if (!is_string($schema)) {
        throw new RuntimeException('Could not prepare the test database schema.');
    }
    foreach (explode(';', $schema) as $statement) {
        if (trim($statement) !== '') {
            $pdo->exec(trim($statement));
        }
    }

    $identifier = bin2hex(random_bytes(5));
    $password = 'TestPassword!2026';
    $superadminEmail = "test-superadmin-{$identifier}@example.invalid";
    $adminEmail = "test-admin-{$identifier}@example.invalid";
    $otherAdminEmail = "test-other-admin-{$identifier}@example.invalid";
    $dispatcherEmail = "test-dispatcher-{$identifier}@example.invalid";
    $historyDispatcherEmail = "test-dispatcher-history-{$identifier}@example.invalid";
    $newDispatcherEmail = "test-dispatcher-new-{$identifier}@example.invalid";
    $newAdminEmail = "test-created-admin-{$identifier}@example.invalid";

    $superadminId = createTestUser($pdo, 'Test Superadmin', $superadminEmail, $password, 'SUPERADMIN');
    $adminId = createTestUser($pdo, 'Test Admin', $adminEmail, $password, 'ADMIN');
    $otherAdminId = createTestUser($pdo, 'Other Test Admin', $otherAdminEmail, $password, 'ADMIN');
    $dispatcherId = createTestUser($pdo, 'Test Dispatcher', $dispatcherEmail, $password, 'DISPATCHER');
    $historyDispatcherId = createTestUser(
        $pdo,
        'Test Dispatcher With Transport',
        $historyDispatcherEmail,
        $password,
        'DISPATCHER'
    );

    $portSocket = stream_socket_server('tcp://127.0.0.1:0', $socketError, $socketErrorMessage);
    if ($portSocket === false) {
        throw new RuntimeException("Could not reserve a local test port: {$socketErrorMessage}");
    }
    $socketAddress = stream_socket_get_name($portSocket, false);
    fclose($portSocket);
    if (!is_string($socketAddress) || !str_contains($socketAddress, ':')) {
        throw new RuntimeException('Could not determine the temporary test server port.');
    }
    $port = substr($socketAddress, strrpos($socketAddress, ':') + 1);
    $baseUrl = "http://127.0.0.1:{$port}";

    $environment = getenv();
    $environment['DB_NAME'] = $testDatabaseName;
    foreach (array_keys($environment) as $key) {
        if (str_starts_with($key, 'SMTP_')) {
            unset($environment[$key]);
        }
    }
    unset($environment['APP_URL']);

    $serverProcess = proc_open(
        [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', __DIR__ . '/../public'],
        [
            0 => ['file', '/dev/null', 'r'],
            1 => ['file', '/dev/null', 'a'],
            2 => ['file', '/dev/null', 'a'],
        ],
        $serverPipes,
        dirname(__DIR__),
        $environment
    );
    if (!is_resource($serverProcess)) {
        throw new RuntimeException('Could not start the temporary PHP server.');
    }

    $serverReady = false;
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $socket = @fsockopen('127.0.0.1', (int) $port, $connectError, $connectErrorMessage, 0.1);
        if (is_resource($socket)) {
            fclose($socket);
            $serverReady = true;
            break;
        }
        $processStatus = proc_get_status($serverProcess);
        if (!$processStatus['running']) {
            break;
        }
        usleep(50_000);
    }
    if (!$serverReady) {
        throw new RuntimeException('The temporary PHP server did not start in time.');
    }

    $guest = new TestBrowser($baseUrl);
    $intro = $guest->get('/');
    expectStatus($intro, 200, 'Public root opens the intro page without authentication');
    expectContains($intro['body'], 'Çdo rrugë.', 'Intro presents the transport management platform');
    expectContains($intro['body'], 'href="?page=login"', 'Intro links to the existing login page');
    expectContains($intro['body'], 'data-journey', 'Intro includes the scroll-linked journey');
    expectContains($intro['body'], 'assets/brand-symbol.svg?v=20261110', 'Intro uses the shared transport logo');
    expectStatus($guest->get('/assets/brand-symbol.svg'), 200, 'Shared transport logo is served');
    expectStatus($guest->get('/assets/intro.css'), 200, 'Intro styles are served');
    expectStatus($guest->get('/assets/intro.js'), 200, 'Intro scroll behavior is served');

    $privateDashboard = $guest->get('/?page=dashboard');
    expectStatus($privateDashboard, 302, 'Dashboard remains protected behind authentication');
    expectLocation($privateDashboard, '?page=login', 'Unauthenticated dashboard access redirects to login');

    $response = $guest->get('/?page=users');
    expectStatus($response, 302, 'Unauthenticated account-management access is redirected');
    expectLocation($response, '?page=login', 'The guest is redirected to login');

    $loginPage = $guest->get('/?page=login');
    expectStatus($loginPage, 200, 'Unauthenticated visitors can open the login form');
    expectContains($loginPage['body'], 'action="?page=login"', 'Login form submits credentials to the login route');
    expectContains($loginPage['body'], 'assets/login.css?v=20261110', 'Login page loads the matching intro theme');
    expectContains($loginPage['body'], 'assets/brand-symbol.svg?v=20261110', 'Login uses the shared transport logo');
    expectContains($loginPage['body'], 'Hyr në platformë', 'Login form uses the intro call-to-action');
    expectStatus($guest->get('/assets/login.css'), 200, 'Login theme stylesheet is served');
    $registerPage = $guest->get('/?page=register');
    expectStatus($registerPage, 200, 'Visitors can open the themed registration page');
    expectContains($registerPage['body'], 'class="login-page auth-page"', 'Registration uses the matching authentication theme');
    expectContains($registerPage['body'], 'assets/brand-symbol.svg?v=20261110', 'Registration uses the shared transport logo');
    $verificationPage = $guest->get('/?page=verify-email');
    expectStatus($verificationPage, 200, 'Visitors can open the themed email verification page');
    expectContains($verificationPage['body'], 'class="login-page auth-page"', 'Email verification uses the matching authentication theme');
    $response = $guest->post('/?page=login', [
        'csrf_token' => $guest->csrfToken($loginPage['body']),
        'email' => $adminEmail,
        'password' => 'incorrect-password',
    ]);
    expectStatus($response, 401, 'Invalid login credentials are rejected');

    $admin = new TestBrowser($baseUrl);
    $response = $admin->login($adminEmail, $password);
    expectStatus($response, 302, 'ADMIN login succeeds');
    expectLocation($response, '?page=dashboard', 'ADMIN login redirects to dashboard');

    $dashboard = $admin->get('/?page=dashboard');
    expectStatus($dashboard, 200, 'ADMIN can open dashboard');
    expectContains($dashboard['body'], 'class="app-page dashboard-page"', 'Dashboard uses the shared and dedicated visual themes');
    expectContains($dashboard['body'], 'assets/brand-symbol.svg?v=20261110', 'Dashboard uses the shared transport logo');
    expectContains($dashboard['body'], 'assets/dashboard.css?v=20261110', 'Dashboard loads its dedicated stylesheet');
    expectContains($dashboard['body'], 'assets/app-theme.css?v=20261110', 'Dashboard also loads the shared application theme');
    expectStatus($admin->get('/assets/dashboard.css'), 200, 'Dashboard theme stylesheet is served');
    expectStatus($admin->get('/assets/app-theme.css'), 200, 'Shared application theme stylesheet is served');
    $authenticatedLogin = $admin->get('/?page=login');
    expectStatus($authenticatedLogin, 302, 'An already authenticated session is redirected from login');
    expectLocation($authenticatedLogin, '?page=dashboard', 'Authenticated login-page redirect uses the existing session');
    expectContains($dashboard['body'], 'mobile-menu-toggle', 'Dashboard exposes the accessible mobile menu');
    expectContains($dashboard['body'], 'assets/app.js?v=20261110', 'Dashboard loads shared frontend behavior');
    foreach (['Transportet', 'Klientët', 'Shoferët', 'Automjetet'] as $label) {
        expectContains($dashboard['body'], $label, "ADMIN navigation includes {$label}");
    }
    foreach (['transports', 'customers', 'drivers', 'vehicles'] as $page) {
        $moduleResponse = $admin->get("/?page={$page}");
        expectStatus($moduleResponse, 200, "ADMIN can open {$page} module");
        expectContains($moduleResponse['body'], 'class="app-page"', "{$page} module uses the shared application theme");
    }
    $appScript = $admin->get('/assets/app.js');
    expectStatus($appScript, 200, 'Shared frontend script is served');

    $modulePage = $admin->get('/?page=customers');
    $response = $admin->post('/?page=customers', [
        'csrf_token' => $admin->csrfToken($modulePage['body']),
        'action' => 'create',
        'company_name' => 'Test Customer ' . $identifier,
        'contact_person' => 'Test Contact',
        'phone' => '+355 600 000 000',
        'email' => "customer-{$identifier}@example.invalid",
        'address' => 'Test address',
    ]);
    expectStatus($response, 302, 'ADMIN can create a customer');
    $customerCheck = $pdo->prepare('SELECT id FROM customers WHERE email = :email');
    $customerCheck->execute(['email' => "customer-{$identifier}@example.invalid"]);
    $customerId = (int) $customerCheck->fetchColumn();
    expectTrue($customerId > 0, 'ADMIN customer creation persists');
    $driverFixture = $pdo->prepare(
        'INSERT INTO drivers (full_name, license_number, phone, email) VALUES (:name, :license, :phone, :email)'
    );
    $driverFixture->execute([
        'name' => 'Schedule Driver One',
        'license' => "SCHED-1-{$identifier}",
        'phone' => '0600000001',
        'email' => "schedule-driver-one-{$identifier}@example.invalid",
    ]);
    $scheduleDriverOne = (int) $pdo->lastInsertId();
    $driverFixture->execute([
        'name' => 'Schedule Driver Two',
        'license' => "SCHED-2-{$identifier}",
        'phone' => '0600000002',
        'email' => "schedule-driver-two-{$identifier}@example.invalid",
    ]);
    $scheduleDriverTwo = (int) $pdo->lastInsertId();
    $vehicleFixture = $pdo->prepare(
        'INSERT INTO vehicles (plate_number, vehicle_type, capacity) VALUES (:plate, :type, :capacity)'
    );
    $vehicleFixture->execute(['plate' => "SCHED-1-{$identifier}", 'type' => 'Test van', 'capacity' => 1]);
    $scheduleVehicleOne = (int) $pdo->lastInsertId();
    $vehicleFixture->execute(['plate' => "SCHED-2-{$identifier}", 'type' => 'Test van', 'capacity' => 1]);
    $scheduleVehicleTwo = (int) $pdo->lastInsertId();

    $transportForm = $admin->get('/?page=transports&action=create');
    $transportCsrf = $admin->csrfToken($transportForm['body']);
    $transportFields = [
        'csrf_token' => $transportCsrf,
        'action' => 'create',
        'customer_id' => (string) $customerId,
        'driver_id' => (string) $scheduleDriverOne,
        'vehicle_id' => (string) $scheduleVehicleOne,
        'pickup_address' => 'Schedule pickup',
        'destination_address' => 'Schedule destination',
        'cargo_description' => 'Schedule test cargo',
        'scheduled_date' => '2025-03-01T10:00',
        'scheduled_end' => '2025-03-01T12:00',
        'status' => 'PLANNED',
    ];
    $scheduledTransport = $admin->post('/?page=transports', $transportFields);
    expectStatus($scheduledTransport, 302, 'ADMIN can create transport with a valid time interval');
    $transportListAfterNotification = $admin->get('/?page=transports');
    expectContains(
        $transportListAfterNotification['body'],
        'Transporti u ruajt, por njoftimi me email nuk u dërgua',
        'SMTP failure does not roll back transport creation and is surfaced to the user'
    );
    $scheduledTransportLookup = $pdo->prepare(
        'SELECT id FROM transports WHERE customer_id = :customer_id AND cargo_description = :cargo AND scheduled_date = :scheduled_date'
    );
    $scheduledTransportLookup->execute([
        'customer_id' => $customerId,
        'cargo' => 'Schedule test cargo',
        'scheduled_date' => '2025-03-01 10:00:00',
    ]);
    $scheduledTransportId = (int) $scheduledTransportLookup->fetchColumn();
    expectTrue($scheduledTransportId > 0, 'Scheduled transport is persisted');

    $financePage = $admin->get('/?page=finances');
    expectStatus($financePage, 200, 'ADMIN can open transport finances');
    expectContains($financePage['body'], 'Shpenzimet dhe fitimi', 'Finance page explains the profit calculation');
    expectContains($financePage['body'], 'Menaxho financat', 'Finance page lists transports for financial management');
    $financeCsrf = $admin->csrfToken($financePage['body']);
    $financeTransportPath = '/?page=finances&transport_id=' . $scheduledTransportId;

    $response = $admin->post('/?page=finances', [
        'csrf_token' => $financeCsrf,
        'finance_action' => 'save_revenue',
        'transport_id' => (string) $scheduledTransportId,
        'revenue' => '1250.50',
    ]);
    expectStatus($response, 302, 'ADMIN can save transport revenue');
    expectLocation($response, '?page=finances&transport_id=' . $scheduledTransportId, 'Revenue save returns to the selected transport finance page');

    foreach ([
        ['FUEL', 'Diesel', '300.25', '2025-03-01'],
        ['TOLL', 'Autostrada', '50.00', '2025-03-01'],
    ] as [$category, $description, $amount, $expenseDate]) {
        $response = $admin->post($financeTransportPath, [
            'csrf_token' => $financeCsrf,
            'finance_action' => 'add_expense',
            'transport_id' => (string) $scheduledTransportId,
            'category' => $category,
            'description' => $description,
            'amount' => $amount,
            'expense_date' => $expenseDate,
        ]);
        expectStatus($response, 302, "ADMIN can add {$category} expense");
    }

    $financeDetail = $admin->get($financeTransportPath);
    expectContains($financeDetail['body'], '1.250,50 €', 'Finance detail displays saved revenue in EUR');
    expectContains($financeDetail['body'], '350,25 €', 'Finance detail sums multiple expenses');
    expectContains($financeDetail['body'], '900,25 €', 'Finance detail calculates transport profit');
    $expenseLookup = $pdo->prepare(
        'SELECT id FROM transport_expenses WHERE transport_id = :transport_id AND description = :description'
    );
    $expenseLookup->execute(['transport_id' => $scheduledTransportId, 'description' => 'Autostrada']);
    $expenseId = (int) $expenseLookup->fetchColumn();
    expectTrue($expenseId > 0, 'Expense records are persisted for their transport');

    $response = $admin->post($financeTransportPath, [
        'csrf_token' => $financeCsrf,
        'finance_action' => 'add_expense',
        'transport_id' => (string) $scheduledTransportId,
        'category' => 'FUEL',
        'description' => 'Invalid negative cost',
        'amount' => '-1.00',
        'expense_date' => '2025-03-01',
    ]);
    expectStatus($response, 422, 'Negative expense amounts are rejected');
    expectContains($response['body'], 'Plotëso llojin', 'Invalid expenses explain the required fields');

    $response = $admin->post($financeTransportPath, [
        'csrf_token' => $financeCsrf,
        'finance_action' => 'delete_expense',
        'transport_id' => (string) $scheduledTransportId,
        'expense_id' => (string) $expenseId,
    ]);
    expectStatus($response, 302, 'ADMIN can delete an expense');
    $expenseCount = $pdo->prepare('SELECT COUNT(*) FROM transport_expenses WHERE transport_id = :id');
    $expenseCount->execute(['id' => $scheduledTransportId]);
    expectTrue((int) $expenseCount->fetchColumn() === 1, 'Deleting an expense updates the transport expense total');

    $overlappingDriver = $transportFields;
    $overlappingDriver['driver_id'] = (string) $scheduleDriverOne;
    $overlappingDriver['vehicle_id'] = (string) $scheduleVehicleTwo;
    $overlappingDriver['scheduled_date'] = '2025-03-01T11:00';
    $overlappingDriver['scheduled_end'] = '2025-03-01T13:00';
    $response = $admin->post('/?page=transports', $overlappingDriver);
    expectStatus($response, 422, 'Overlapping transport for the same driver is rejected');
    expectContains($response['body'], 'Ky shoferi është i caktuar', 'Driver conflict explains the scheduling issue');

    $completedOverlap = $overlappingDriver;
    $completedOverlap['status'] = 'COMPLETED';
    $response = $admin->post('/?page=transports', $completedOverlap);
    expectStatus($response, 422, 'Completed transports still count as scheduling conflicts');

    $overlappingVehicle = $transportFields;
    $overlappingVehicle['driver_id'] = (string) $scheduleDriverTwo;
    $overlappingVehicle['vehicle_id'] = (string) $scheduleVehicleOne;
    $overlappingVehicle['scheduled_date'] = '2025-03-01T11:00';
    $overlappingVehicle['scheduled_end'] = '2025-03-01T13:00';
    $response = $admin->post('/?page=transports', $overlappingVehicle);
    expectStatus($response, 422, 'Overlapping transport for the same vehicle is rejected');
    expectContains($response['body'], 'Ky automjeti është i caktuar', 'Vehicle conflict explains the scheduling issue');

    $adjacentTransport = $transportFields;
    $adjacentTransport['scheduled_date'] = '2025-03-01T12:00';
    $adjacentTransport['scheduled_end'] = '2025-03-01T13:00';
    expectStatus(
        $admin->post('/?page=transports', $adjacentTransport),
        302,
        'Back-to-back transports are allowed when their intervals only touch'
    );

    $invalidInterval = $transportFields;
    $invalidInterval['scheduled_end'] = '2025-03-01T10:00';
    $response = $admin->post('/?page=transports', $invalidInterval);
    expectStatus($response, 422, 'Transport end must be later than its start');
    expectContains($response['body'], 'duhet të jenë pas fillimit', 'Invalid interval displays a clear validation message');

    $updateFields = $transportFields;
    $updateFields['action'] = 'update';
    $updateFields['id'] = (string) $scheduledTransportId;
    $updateFields['status'] = 'IN_PROGRESS';
    expectStatus(
        $admin->post('/?page=transports', $updateFields),
        302,
        'Status changes can be saved on an existing transport'
    );
    $statusNotification = $admin->get('/?page=transports');
    expectContains(
        $statusNotification['body'],
        'Transporti u ruajt, por njoftimi me email nuk u dërgua',
        'Status-change email failures are surfaced without rolling back the status'
    );

    $cancelledFields = $transportFields;
    $cancelledFields['driver_id'] = (string) $scheduleDriverTwo;
    $cancelledFields['vehicle_id'] = (string) $scheduleVehicleTwo;
    $cancelledFields['status'] = 'CANCELLED';
    expectStatus(
        $admin->post('/?page=transports', $cancelledFields),
        302,
        'Cancelled transports do not reserve a driver or vehicle'
    );
    $afterCancelled = $transportFields;
    $afterCancelled['driver_id'] = (string) $scheduleDriverTwo;
    $afterCancelled['vehicle_id'] = (string) $scheduleVehicleTwo;
    expectStatus(
        $admin->post('/?page=transports', $afterCancelled),
        302,
        'Cancelled transports are ignored when checking later assignments'
    );

    $transportStatement = $pdo->prepare(
        "INSERT INTO transports
            (customer_id, created_by, pickup_address, destination_address, cargo_description, scheduled_date, scheduled_end, status)
        VALUES (:customer_id, :created_by, 'Test pickup', 'Test destination', 'Test cargo', CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL 1 HOUR), 'PLANNED')"
    );
    $transportStatement->execute([
        'customer_id' => $customerId,
        'created_by' => $historyDispatcherId,
    ]);

    $reportFixture = $pdo->prepare(
        'INSERT INTO transports
            (customer_id, created_by, pickup_address, destination_address, cargo_description, scheduled_date, scheduled_end, status)
        VALUES
            (:customer_id, :created_by, :pickup_address, :destination_address, :cargo_description, :scheduled_date, :scheduled_end, :status)'
    );
    foreach ([
        ['2025-01-02 09:00:00', 'PLANNED', 'Report fixture'],
        ['2025-01-03 09:00:00', 'COMPLETED', 'Report fixture'],
        ['2024-12-30 09:00:00', 'IN_PROGRESS', 'Outside report fixture'],
    ] as [$scheduledDate, $status, $cargoDescription]) {
        $reportFixture->execute([
            'customer_id' => $customerId,
            'created_by' => $adminId,
            'pickup_address' => 'Report pickup',
            'destination_address' => 'Report destination',
            'cargo_description' => $cargoDescription,
            'scheduled_date' => $scheduledDate,
            'scheduled_end' => date('Y-m-d H:i:s', strtotime($scheduledDate . ' +2 hours')),
            'status' => $status,
        ]);
    }

    $comparisonReport = $admin->get(
        '/?page=dashboard&date_from=2025-01-01&date_to=2025-01-03'
    );
    expectStatus($comparisonReport, 200, 'Dashboard accepts report date ranges');
    expectContains($comparisonReport['body'], '2 gjithsej', 'Report totals the selected period');
    expectContains($comparisonReport['body'], '2024-12-29 — 2024-12-31', 'Report shows the equal previous period');
    expectContains($comparisonReport['body'], '100%', 'Report calculates percentage change from the previous period');
    expectContains($comparisonReport['body'], 'Eksporto CSV', 'Report exposes CSV export');

    $emptyComparisonReport = $admin->get(
        '/?page=dashboard&date_from=2025-02-01&date_to=2025-02-03'
    );
    expectStatus($emptyComparisonReport, 200, 'Dashboard supports empty report periods');
    expectContains($emptyComparisonReport['body'], 'pa bazë krahasimi', 'Report explains percentage changes when the previous period is empty');

    $reportCsv = $admin->get(
        '/?page=dashboard&export=csv&date_from=2025-01-01&date_to=2025-01-03'
    );
    expectStatus($reportCsv, 200, 'Dashboard report can be exported as CSV');
    expectContains($reportCsv['body'], 'Report fixture', 'CSV includes transports from the selected period');
    expectNotContains($reportCsv['body'], 'Outside report fixture', 'CSV excludes transports outside the selected period');

    $transportList = $admin->get('/?page=transports');
    expectContains($transportList['body'], 'data-table-enhancement', 'Transport results expose client-side table controls');
    expectContains($transportList['body'], 'data-confirm=', 'Delete actions expose confirmation prompts');

    $adminAccountsPage = $admin->get('/?page=users');
    expectStatus($adminAccountsPage, 200, 'ADMIN can open account management');
    expectContains($adminAccountsPage['body'], 'assets/app-theme.css?v=20261110', 'Account management loads the shared application theme');
    expectContains($adminAccountsPage['body'], $dispatcherEmail, 'ADMIN sees dispatcher accounts');
    expectContains($adminAccountsPage['body'], $historyDispatcherEmail, 'ADMIN sees dispatcher with transport history');
    expectNotContains($adminAccountsPage['body'], $adminEmail, 'ADMIN cannot see own ADMIN account');
    expectNotContains($adminAccountsPage['body'], $otherAdminEmail, 'ADMIN cannot see other ADMIN accounts');
    expectNotContains($adminAccountsPage['body'], $superadminEmail, 'ADMIN cannot see SUPERADMIN accounts');

    $adminCsrf = $admin->csrfToken($adminAccountsPage['body']);
    $response = $admin->post('/?page=users', [
        'csrf_token' => $adminCsrf,
        'id' => (string) $otherAdminId,
        'account_action' => 'reset_password',
        'password' => 'OtherPassword!2026',
        'password_confirmation' => 'OtherPassword!2026',
    ]);
    expectStatus($response, 403, 'ADMIN cannot change another ADMIN password');

    $response = $admin->post('/?page=users', [
        'csrf_token' => $adminCsrf,
        'id' => (string) $adminId,
        'account_action' => 'update_email',
        'email' => "admin-new-{$identifier}@example.invalid",
    ]);
    expectStatus($response, 403, 'ADMIN cannot change own account through account management');

    $response = $admin->post('/?page=users', [
        'csrf_token' => $adminCsrf,
        'id' => (string) $dispatcherId,
        'account_action' => 'reset_password',
        'password' => 'DispatcherPassword!2026',
        'password_confirmation' => 'DispatcherPassword!2026',
    ]);
    expectStatus($response, 302, 'ADMIN can reset a dispatcher password');
    $dispatcherWithNewPassword = new TestBrowser($baseUrl);
    expectStatus(
        $dispatcherWithNewPassword->login($dispatcherEmail, 'DispatcherPassword!2026'),
        302,
        'Dispatcher can log in with the new password'
    );

    $response = $admin->post('/?page=users', [
        'csrf_token' => $adminCsrf,
        'id' => (string) $dispatcherId,
        'account_action' => 'update_email',
        'email' => $newDispatcherEmail,
    ]);
    expectStatus($response, 302, 'ADMIN can change a dispatcher email');
    $dispatcherState = $pdo->prepare('SELECT is_active, email_verified_at FROM users WHERE id = :id');
    $dispatcherState->execute(['id' => $dispatcherId]);
    $dispatcherRecord = $dispatcherState->fetch();
    expectTrue(
        (int) $dispatcherRecord['is_active'] === 0 && $dispatcherRecord['email_verified_at'] === null,
        'Changing dispatcher email requires verification'
    );
    $unverifiedDispatcher = new TestBrowser($baseUrl);
    expectStatus(
        $unverifiedDispatcher->login($newDispatcherEmail, 'DispatcherPassword!2026'),
        401,
        'Dispatcher cannot log in with an unverified new email'
    );

    $response = $admin->post('/?page=users', [
        'csrf_token' => $adminCsrf,
        'id' => (string) $dispatcherId,
        'account_action' => 'delete',
    ]);
    expectStatus($response, 302, 'ADMIN can delete a dispatcher without transports');
    $dispatcherExists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
    $dispatcherExists->execute(['id' => $dispatcherId]);
    expectTrue((int) $dispatcherExists->fetchColumn() === 0, 'Deleted dispatcher is removed');
    $response = $admin->post('/?page=users', [
        'csrf_token' => $adminCsrf,
        'id' => (string) $historyDispatcherId,
        'account_action' => 'delete',
    ]);
    expectStatus($response, 409, 'ADMIN cannot delete a dispatcher with transport history');
    $historyDispatcherExists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
    $historyDispatcherExists->execute(['id' => $historyDispatcherId]);
    expectTrue((int) $historyDispatcherExists->fetchColumn() === 1, 'Protected dispatcher account remains');

    $addAdminPage = $admin->get('/?page=add-admin');
    $response = $admin->post('/?page=add-admin', [
        'csrf_token' => $admin->csrfToken($addAdminPage['body']),
        'full_name' => 'Admin Created By Admin',
        'email' => $newAdminEmail,
        'password' => 'NewAdminPassword!2026',
        'password_confirmation' => 'NewAdminPassword!2026',
    ]);
    expectStatus($response, 302, 'ADMIN can create another ADMIN account');
    $newAdminCheck = $pdo->prepare('SELECT role, is_active FROM users WHERE email = :email');
    $newAdminCheck->execute(['email' => $newAdminEmail]);
    $newAdminRecord = $newAdminCheck->fetch();
    expectTrue(
        $newAdminRecord !== false
            && $newAdminRecord['role'] === 'ADMIN'
            && (int) $newAdminRecord['is_active'] === 0,
        'New administrator receives ADMIN role and remains unverified'
    );
    $unverifiedAdmin = new TestBrowser($baseUrl);
    expectStatus(
        $unverifiedAdmin->login($newAdminEmail, 'NewAdminPassword!2026'),
        401,
        'New administrator cannot log in before email verification'
    );

    $dispatcher = new TestBrowser($baseUrl);
    expectStatus($dispatcher->login($dispatcherEmail, $password), 401, 'Dispatcher cannot log in after deletion');
    $activeDispatcherId = createTestUser(
        $pdo,
        'Second Test Dispatcher',
        "test-dispatcher-second-{$identifier}@example.invalid",
        $password,
        'DISPATCHER'
    );
    $dispatcher = new TestBrowser($baseUrl);
    expectStatus(
        $dispatcher->login("test-dispatcher-second-{$identifier}@example.invalid", $password),
        302,
        'DISPATCHER login succeeds'
    );
    expectStatus($dispatcher->get('/?page=customers'), 200, 'DISPATCHER can view customers');
    expectStatus($dispatcher->get('/?page=users'), 403, 'DISPATCHER cannot access account management');
    expectStatus($dispatcher->get('/?page=finances'), 403, 'DISPATCHER cannot access transport finances');
    expectContains(
        $dispatcher->get('/?page=finances')['body'],
        'Qasja në financa është e kufizuar',
        'Finance access denial is explained to DISPATCHER'
    );
    $dispatcherCustomers = $dispatcher->get('/?page=customers');
    $response = $dispatcher->post('/?page=customers', [
        'csrf_token' => $dispatcher->csrfToken($dispatcherCustomers['body']),
        'action' => 'create',
        'company_name' => 'Forbidden Customer ' . $identifier,
        'contact_person' => 'Test Contact',
        'phone' => '+355 600 000 001',
        'email' => "forbidden-customer-{$identifier}@example.invalid",
        'address' => 'Test address',
    ]);
    expectStatus($response, 403, 'DISPATCHER cannot create customers');
    $forbiddenCustomer = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE email = :email');
    $forbiddenCustomer->execute(['email' => "forbidden-customer-{$identifier}@example.invalid"]);
    expectTrue((int) $forbiddenCustomer->fetchColumn() === 0, 'Forbidden customer creation has no database effect');
    $pdo->prepare('DELETE FROM users WHERE id = :id')->execute(['id' => $activeDispatcherId]);

    $superadmin = new TestBrowser($baseUrl);
    expectStatus($superadmin->login($superadminEmail, $password), 302, 'SUPERADMIN login succeeds');
    expectStatus($superadmin->get('/?page=finances'), 200, 'SUPERADMIN can open transport finances');
    $superadminAccountsPage = $superadmin->get('/?page=users');
    expectStatus($superadminAccountsPage, 200, 'SUPERADMIN can open account management');
    expectContains($superadminAccountsPage['body'], $superadminEmail, 'SUPERADMIN sees own account');
    expectContains($superadminAccountsPage['body'], $adminEmail, 'SUPERADMIN sees ADMIN accounts');
    expectContains(
        $superadminAccountsPage['body'],
        $historyDispatcherEmail,
        'SUPERADMIN sees dispatcher with transport history'
    );

    $superadminCsrf = $superadmin->csrfToken($superadminAccountsPage['body']);
    $response = $superadmin->post('/?page=users', [
        'csrf_token' => $superadminCsrf,
        'id' => (string) $superadminId,
        'account_action' => 'delete',
    ]);
    expectStatus($response, 409, 'SUPERADMIN cannot delete own account');

    $response = $superadmin->post('/?page=users', [
        'csrf_token' => $superadminCsrf,
        'id' => (string) $otherAdminId,
        'account_action' => 'reset_password',
        'password' => 'AdminResetPassword!2026',
        'password_confirmation' => 'AdminResetPassword!2026',
    ]);
    expectStatus($response, 302, 'SUPERADMIN can reset an ADMIN password');
    $adminWithNewPassword = new TestBrowser($baseUrl);
    expectStatus(
        $adminWithNewPassword->login($otherAdminEmail, 'AdminResetPassword!2026'),
        302,
        'ADMIN can log in with the Superadmin-assigned password'
    );

    $response = $superadmin->post('/?page=users', [
        'csrf_token' => $superadminCsrf,
        'id' => (string) $otherAdminId,
        'account_action' => 'update_email',
        'email' => "test-other-admin-new-{$identifier}@example.invalid",
    ]);
    expectStatus($response, 302, 'SUPERADMIN can change an ADMIN email');
    $otherAdminState = $pdo->prepare('SELECT is_active, email_verified_at FROM users WHERE id = :id');
    $otherAdminState->execute(['id' => $otherAdminId]);
    $otherAdminRecord = $otherAdminState->fetch();
    expectTrue(
        (int) $otherAdminRecord['is_active'] === 0 && $otherAdminRecord['email_verified_at'] === null,
        'Changing ADMIN email requires verification'
    );

    $response = $superadmin->post('/?page=users', [
        'csrf_token' => $superadminCsrf,
        'id' => (string) $otherAdminId,
        'account_action' => 'delete',
    ]);
    expectStatus($response, 302, 'SUPERADMIN can delete an ADMIN without transports');
    $otherAdminExists = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id = :id');
    $otherAdminExists->execute(['id' => $otherAdminId]);
    expectTrue((int) $otherAdminExists->fetchColumn() === 0, 'Deleted ADMIN is removed');
    $response = $superadmin->post('/?page=users', [
        'csrf_token' => $superadminCsrf,
        'id' => (string) $historyDispatcherId,
        'account_action' => 'delete',
    ]);
    expectStatus($response, 409, 'SUPERADMIN cannot delete an account with transport history');
    $historyDispatcherExists->execute(['id' => $historyDispatcherId]);
    expectTrue((int) $historyDispatcherExists->fetchColumn() === 1, 'Protected account remains after Superadmin request');

    $response = $superadmin->post('/?page=users', [
        'csrf_token' => $superadminCsrf,
        'id' => (string) $superadminId,
        'account_action' => 'reset_password',
        'password' => 'NewSuperadminPassword!2026',
        'password_confirmation' => 'NewSuperadminPassword!2026',
    ]);
    expectStatus($response, 302, 'SUPERADMIN can reset own password');
    $superadminWithNewPassword = new TestBrowser($baseUrl);
    expectStatus(
        $superadminWithNewPassword->login($superadminEmail, 'NewSuperadminPassword!2026'),
        302,
        'SUPERADMIN can log in with own new password'
    );

    $superadminPage = $superadmin->get('/?page=users');
    $response = $superadmin->post('/?page=users', [
        'csrf_token' => $superadmin->csrfToken($superadminPage['body']),
        'id' => (string) $superadminId,
        'account_action' => 'update_email',
        'email' => "test-superadmin-new-{$identifier}@example.invalid",
    ]);
    expectStatus($response, 302, 'SUPERADMIN can change own email');
    $superadminState = $pdo->prepare('SELECT role, is_active, email_verified_at FROM users WHERE id = :id');
    $superadminState->execute(['id' => $superadminId]);
    $superadminRecord = $superadminState->fetch();
    expectTrue(
        $superadminRecord['role'] === 'SUPERADMIN'
            && (int) $superadminRecord['is_active'] === 0
            && $superadminRecord['email_verified_at'] === null,
        'Changing own email preserves SUPERADMIN role and requires verification'
    );
    $unverifiedSuperadmin = new TestBrowser($baseUrl);
    expectStatus(
        $unverifiedSuperadmin->login("test-superadmin-new-{$identifier}@example.invalid", 'NewSuperadminPassword!2026'),
        401,
        'SUPERADMIN cannot log in with own unverified new email'
    );

    echo "All integration tests passed.\n";
} catch (Throwable $exception) {
    $testExitCode = 1;
    fwrite(STDERR, 'FAIL ' . $exception->getMessage() . PHP_EOL);
} finally {
    if (is_resource($serverProcess)) {
        proc_terminate($serverProcess);
        proc_close($serverProcess);
    }

    if ($testDatabaseCreated && is_string($testDatabaseName)) {
        try {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $serverConnection = new PDO(
                "mysql:host={$host};port={$port};charset=utf8mb4",
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASSWORD') ?: '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $serverConnection->exec("DROP DATABASE `{$testDatabaseName}`");
            echo "Temporary test database cleaned up.\n";
        } catch (Throwable $exception) {
            $testExitCode = 1;
            fwrite(STDERR, 'FAIL Could not clean up test database: ' . $exception->getMessage() . PHP_EOL);
        }
    }
}

exit($testExitCode);
