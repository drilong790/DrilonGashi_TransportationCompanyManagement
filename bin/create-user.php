<?php

declare(strict_types=1);

use App\Database\Connection;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../src/Database/Connection.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $value = fgets(STDIN);
    if ($value === false) {
        throw new RuntimeException('Nuk u mor përgjigje nga terminali.');
    }
    return trim($value);
}

function promptPassword(): string
{
    if (!function_exists('stream_isatty') || !stream_isatty(STDIN) || !function_exists('shell_exec')) {
        throw new RuntimeException('Nise skriptin në terminal interaktiv për të futur fjalëkalimin në mënyrë të sigurt.');
    }

    $terminalMode = shell_exec('stty -g');
    if (!is_string($terminalMode) || trim($terminalMode) === '') {
        throw new RuntimeException('Nuk mund të lexohet konfigurimi i terminalit për futje të sigurt të fjalëkalimit.');
    }

    fwrite(STDOUT, 'Fjalëkalimi (minimumi 12 karaktere): ');
    exec('stty -echo', $output, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('Terminali nuk mund të vendoset në mënyrën e sigurt për futjen e fjalëkalimit.');
    }
    try {
        $password = fgets(STDIN);
    } finally {
        exec('stty ' . escapeshellarg(trim($terminalMode)), $output, $restoreExitCode);
        fwrite(STDOUT, PHP_EOL);
    }
    if ($restoreExitCode !== 0) {
        throw new RuntimeException('Nuk mund të rikthehet konfigurimi i terminalit.');
    }

    if ($password === false) {
        throw new RuntimeException('Nuk u mor fjalëkalimi.');
    }
    return rtrim($password, "\r\n");
}

try {
    $fullName = prompt('Emri i plotë: ');
    $email = prompt('Email: ');
    $role = strtoupper(prompt('Roli (ADMIN/DISPATCHER): '));
    if ($fullName === '' || mb_strlen($fullName) > 150) {
        throw new RuntimeException('Emri është i detyrueshëm dhe duhet të jetë maksimumi 150 karaktere.');
    }
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 150) {
        throw new RuntimeException('Shkruaj një email të vlefshëm (maksimumi 150 karaktere).');
    }
    if (!in_array($role, ['ADMIN', 'DISPATCHER'], true)) {
        throw new RuntimeException('Roli duhet të jetë ADMIN ose DISPATCHER.');
    }

    $password = promptPassword();
    if (strlen($password) < 12) {
        throw new RuntimeException('Fjalëkalimi duhet të ketë të paktën 12 karaktere.');
    }
    $confirmation = promptPassword();
    if (!hash_equals($password, $confirmation)) {
        throw new RuntimeException('Fjalëkalimet nuk përputhen.');
    }

    $pdo = Connection::create();
    $statement = $pdo->prepare(
        'INSERT INTO users (full_name, email, password_hash, role, is_active, email_verified_at)
        VALUES (:full_name, :email, :password_hash, :role, 1, CURRENT_TIMESTAMP)'
    );
    $statement->execute([
        'full_name' => $fullName,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'role' => $role,
    ]);

    fwrite(STDOUT, "Llogaria u krijua me sukses. Hyr me email-in e regjistruar.\n");
} catch (\PDOException $exception) {
    if (($exception->errorInfo[1] ?? null) === 1062) {
        fwrite(STDERR, "Ky email është përdorur tashmë për një llogari.\n");
        exit(1);
    }
    error_log('Creating CLI user failed: ' . $exception->getMessage());
    fwrite(STDERR, "Llogaria nuk u krijua për shkak të gabimit në databazë.\n");
    exit(1);
} catch (RuntimeException $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
