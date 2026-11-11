<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Authentication;
use PDO;

final class FinanceController
{
    private const CATEGORIES = [
        'FUEL' => 'Karburant',
        'TOLL' => 'Toll / taksa rruge',
        'MAINTENANCE' => 'Servis dhe mirëmbajtje',
        'OTHER' => 'Tjetër',
    ];

    public static function handle(PDO $pdo, array $user): array
    {
        $state = [
            'canManage' => in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true),
            'error' => null,
            'message' => $_SESSION['flash_message'] ?? null,
            'csrfToken' => Authentication::csrfToken(),
            'categories' => self::CATEGORIES,
            'transports' => [],
            'selectedTransport' => null,
            'expenses' => [],
            'expenseValues' => [
                'category' => 'FUEL',
                'description' => '',
                'amount' => '',
                'expense_date' => date('Y-m-d'),
            ],
        ];
        unset($_SESSION['flash_message']);

        if (!$state['canManage']) {
            http_response_code(403);
            $state['error'] = 'Vetëm administratori ose superadministratori mund të shohë dhe ndryshojë financat.';
            return $state;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::handlePost($pdo, $user, $state);
        }

        $transportStatement = $pdo->query(
            'SELECT t.id, c.company_name AS customer_name, t.scheduled_date, t.status, t.revenue,
                COALESCE(SUM(e.amount), 0.00) AS total_expenses,
                t.revenue - COALESCE(SUM(e.amount), 0.00) AS profit
            FROM transports t
            JOIN customers c ON c.id = t.customer_id
            LEFT JOIN transport_expenses e ON e.transport_id = t.id
            GROUP BY t.id, c.company_name, t.scheduled_date, t.status, t.revenue
            ORDER BY t.scheduled_date DESC, t.id DESC'
        );
        $state['transports'] = $transportStatement->fetchAll();

        $transportId = self::positiveId($_GET['transport_id'] ?? $_POST['transport_id'] ?? null);
        if ($transportId !== null) {
            $selectedStatement = $pdo->prepare(
                'SELECT t.id, c.company_name AS customer_name, t.scheduled_date, t.scheduled_end,
                    t.status, t.revenue, COALESCE(SUM(e.amount), 0.00) AS total_expenses,
                    t.revenue - COALESCE(SUM(e.amount), 0.00) AS profit
                FROM transports t
                JOIN customers c ON c.id = t.customer_id
                LEFT JOIN transport_expenses e ON e.transport_id = t.id
                WHERE t.id = :id
                GROUP BY t.id, c.company_name, t.scheduled_date, t.scheduled_end, t.status, t.revenue'
            );
            $selectedStatement->execute(['id' => $transportId]);
            $state['selectedTransport'] = $selectedStatement->fetch() ?: null;
            if ($state['selectedTransport'] === null && $state['error'] === null) {
                http_response_code(404);
                $state['error'] = 'Transporti i zgjedhur nuk u gjet.';
            } elseif ($state['selectedTransport'] !== null) {
                $expenseStatement = $pdo->prepare(
                    'SELECT e.id, e.category, e.description, e.amount, e.expense_date, u.full_name AS created_by_name
                    FROM transport_expenses e
                    LEFT JOIN users u ON u.id = e.created_by
                    WHERE e.transport_id = :transport_id
                    ORDER BY e.expense_date DESC, e.id DESC'
                );
                $expenseStatement->execute(['transport_id' => $transportId]);
                $state['expenses'] = $expenseStatement->fetchAll();
            }
        }

        return $state;
    }

    private static function handlePost(PDO $pdo, array $user, array &$state): void
    {
        if (!Authentication::isValidCsrfToken($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            $state['error'] = 'Kërkesa skadoi ose nuk është e vlefshme. Rifresko faqen dhe provo përsëri.';
            return;
        }

        $action = $_POST['finance_action'] ?? null;
        $transportId = self::positiveId($_POST['transport_id'] ?? null);
        if ($transportId === null) {
            http_response_code(422);
            $state['error'] = 'Transporti i zgjedhur nuk është i vlefshëm.';
            return;
        }

        $transportCheck = $pdo->prepare('SELECT id FROM transports WHERE id = :id');
        $transportCheck->execute(['id' => $transportId]);
        if ($transportCheck->fetchColumn() === false) {
            http_response_code(404);
            $state['error'] = 'Transporti i zgjedhur nuk u gjet.';
            return;
        }

        if ($action === 'save_revenue') {
            $amount = self::money($_POST['revenue'] ?? null);
            if ($amount === null) {
                http_response_code(422);
                $state['error'] = 'Të hyrat duhet të jenë shumë jo-negative, me maksimumi dy shifra pas presjes.';
                return;
            }

            $statement = $pdo->prepare('UPDATE transports SET revenue = :revenue WHERE id = :id');
            $statement->execute(['revenue' => $amount, 'id' => $transportId]);
            self::redirect($transportId, 'Të hyrat e transportit u ruajtën.');
        }

        if ($action === 'add_expense') {
            $category = $_POST['category'] ?? null;
            $description = $_POST['description'] ?? null;
            $amount = self::money($_POST['amount'] ?? null);
            $expenseDate = $_POST['expense_date'] ?? null;
            $state['expenseValues'] = [
                'category' => is_string($category) ? $category : '',
                'description' => is_string($description) ? trim($description) : '',
                'amount' => is_string($_POST['amount'] ?? null) ? trim($_POST['amount']) : '',
                'expense_date' => is_string($expenseDate) ? $expenseDate : '',
            ];
            if (
                !is_string($category)
                || !array_key_exists($category, self::CATEGORIES)
                || !is_string($description)
                || trim($description) === ''
                || mb_strlen(trim($description)) > 255
                || $amount === null
                || !self::validDate($expenseDate)
            ) {
                http_response_code(422);
                $state['error'] = 'Plotëso llojin, përshkrimin (maksimumi 255 karaktere), shumën dhe datën e vlefshme të shpenzimit.';
                return;
            }

            $statement = $pdo->prepare(
                'INSERT INTO transport_expenses
                    (transport_id, category, description, amount, expense_date, created_by)
                VALUES (:transport_id, :category, :description, :amount, :expense_date, :created_by)'
            );
            $statement->execute([
                'transport_id' => $transportId,
                'category' => $category,
                'description' => trim($description),
                'amount' => $amount,
                'expense_date' => $expenseDate,
                'created_by' => (int) $user['id'],
            ]);
            self::redirect($transportId, 'Shpenzimi u regjistrua.');
        }

        if ($action === 'delete_expense') {
            $expenseId = self::positiveId($_POST['expense_id'] ?? null);
            if ($expenseId === null) {
                http_response_code(422);
                $state['error'] = 'Shpenzimi i zgjedhur nuk është i vlefshëm.';
                return;
            }
            $statement = $pdo->prepare(
                'DELETE FROM transport_expenses WHERE id = :id AND transport_id = :transport_id'
            );
            $statement->execute(['id' => $expenseId, 'transport_id' => $transportId]);
            if ($statement->rowCount() === 0) {
                http_response_code(404);
                $state['error'] = 'Shpenzimi nuk u gjet ose i përket një transporti tjetër.';
                return;
            }
            self::redirect($transportId, 'Shpenzimi u fshi.');
        }

        http_response_code(400);
        $state['error'] = 'Veprimi financiar nuk është i vlefshëm.';
    }

    private static function money(mixed $value): ?string
    {
        if (!is_string($value) || preg_match('/^\d{1,10}(?:\.\d{1,2})?$/', trim($value)) !== 1) {
            return null;
        }

        [$whole, $fraction] = array_pad(explode('.', trim($value), 2), 2, '');
        $whole = ltrim($whole, '0');
        return ($whole === '' ? '0' : $whole) . '.' . str_pad($fraction, 2, '0');
    }

    private static function validDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function positiveId(mixed $value): ?int
    {
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }

    private static function redirect(int $transportId, string $message): void
    {
        $_SESSION['flash_message'] = $message;
        header('Location: ?page=finances&transport_id=' . $transportId);
        exit;
    }
}
