<?php

declare(strict_types=1);

namespace App\Controllers;

use DateTimeImmutable;
use PDO;

final class CalendarController
{
    private const STATUSES = [
        'PLANNED' => 'Planifikuar',
        'IN_PROGRESS' => 'Në proces',
        'COMPLETED' => 'Përfunduar',
        'CANCELLED' => 'Anuluar',
    ];

    public static function handle(PDO $pdo): array
    {
        $error = null;
        $view = $_GET['view'] ?? 'week';
        if (!is_string($view) || !in_array($view, ['day', 'week'], true)) {
            http_response_code(422);
            $error = 'Zgjidh pamjen ditore ose javore.';
            $view = 'week';
        }

        $selectedDate = $_GET['date'] ?? date('Y-m-d');
        if (!self::isDate($selectedDate)) {
            http_response_code(422);
            $error = 'Zgjidh një datë të vlefshme për kalendarin.';
            $selectedDate = date('Y-m-d');
        }
        $selectedDay = new DateTimeImmutable($selectedDate);
        $periodStart = $view === 'week'
            ? $selectedDay->modify('monday this week')
            : $selectedDay;
        $periodEnd = $periodStart->modify($view === 'week' ? '+7 days' : '+1 day');

        $status = $_GET['status'] ?? '';
        if (!is_string($status) || ($status !== '' && !array_key_exists($status, self::STATUSES))) {
            http_response_code(422);
            $error = 'Filtri i statusit nuk është i vlefshëm.';
            $status = '';
        }

        $driverId = self::optionalPositiveId($_GET['driver_id'] ?? '');
        if ($driverId === null && ($_GET['driver_id'] ?? '') !== '') {
            http_response_code(422);
            $error = 'Filtri i shoferit nuk është i vlefshëm.';
        }
        $vehicleId = self::optionalPositiveId($_GET['vehicle_id'] ?? '');
        if ($vehicleId === null && ($_GET['vehicle_id'] ?? '') !== '') {
            http_response_code(422);
            $error = 'Filtri i automjetit nuk është i vlefshëm.';
        }

        $conditions = [
            't.scheduled_date >= :range_start',
            't.scheduled_date < :range_end',
        ];
        $parameters = [
            'range_start' => $periodStart->format('Y-m-d 00:00:00'),
            'range_end' => $periodEnd->format('Y-m-d 00:00:00'),
        ];
        if ($status !== '') {
            $conditions[] = 't.status = :status';
            $parameters['status'] = $status;
        }
        if ($driverId !== null) {
            $conditions[] = 't.driver_id = :driver_id';
            $parameters['driver_id'] = $driverId;
        }
        if ($vehicleId !== null) {
            $conditions[] = 't.vehicle_id = :vehicle_id';
            $parameters['vehicle_id'] = $vehicleId;
        }

        $transportStatement = $pdo->prepare(
            'SELECT t.id, t.customer_id, t.driver_id, t.vehicle_id, t.pickup_address,
                t.destination_address, t.cargo_description, t.scheduled_date, t.scheduled_end, t.status,
                c.company_name AS customer_name, d.full_name AS driver_name, v.plate_number
            FROM transports t
            JOIN customers c ON c.id = t.customer_id
            LEFT JOIN drivers d ON d.id = t.driver_id
            LEFT JOIN vehicles v ON v.id = t.vehicle_id
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY t.scheduled_date, t.id'
        );
        $transportStatement->execute($parameters);
        $transportsByDate = [];
        foreach ($transportStatement->fetchAll() as $transport) {
            $transportsByDate[substr($transport['scheduled_date'], 0, 10)][] = $transport;
        }

        $weekdayLabels = [
            1 => 'E hënë',
            2 => 'E martë',
            3 => 'E mërkurë',
            4 => 'E enjte',
            5 => 'E premte',
            6 => 'E shtunë',
            7 => 'E diel',
        ];
        $days = [];
        $dayCount = $view === 'week' ? 7 : 1;
        for ($offset = 0; $offset < $dayCount; $offset++) {
            $date = $periodStart->modify("+{$offset} days");
            $dateValue = $date->format('Y-m-d');
            $days[] = [
                'date' => $dateValue,
                'label' => $weekdayLabels[(int) $date->format('N')],
                'display_date' => $date->format('d.m.Y'),
                'is_today' => $dateValue === date('Y-m-d'),
                'transports' => $transportsByDate[$dateValue] ?? [],
            ];
        }

        return [
            'view' => $view,
            'selectedDate' => $selectedDay->format('Y-m-d'),
            'periodStart' => $periodStart->format('Y-m-d'),
            'periodEnd' => $periodEnd->modify('-1 day')->format('Y-m-d'),
            'previousDate' => $periodStart->modify($view === 'week' ? '-7 days' : '-1 day')->format('Y-m-d'),
            'nextDate' => $periodStart->modify($view === 'week' ? '+7 days' : '+1 day')->format('Y-m-d'),
            'today' => date('Y-m-d'),
            'error' => $error,
            'filters' => [
                'status' => $status,
                'driver_id' => $driverId === null ? '' : (string) $driverId,
                'vehicle_id' => $vehicleId === null ? '' : (string) $vehicleId,
            ],
            'statuses' => self::STATUSES,
            'drivers' => $pdo->query('SELECT id, full_name FROM drivers ORDER BY full_name')->fetchAll(),
            'vehicles' => $pdo->query('SELECT id, plate_number FROM vehicles ORDER BY plate_number')->fetchAll(),
            'days' => $days,
            'transportCount' => array_sum(array_map(
                static fn (array $day): int => count($day['transports']),
                $days
            )),
        ];
    }

    private static function isDate(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function optionalPositiveId(mixed $value): ?int
    {
        if ($value === '') {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            return null;
        }
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $id === false ? null : $id;
    }
}
