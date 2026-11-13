<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$buildCalendarUrl = static function (array $overrides = []) use ($calendarData): string {
    $parameters = [
        'page' => 'calendar',
        'view' => $calendarData['view'],
        'date' => $calendarData['selectedDate'],
        'status' => $calendarData['filters']['status'],
        'driver_id' => $calendarData['filters']['driver_id'],
        'vehicle_id' => $calendarData['filters']['vehicle_id'],
    ];
    $parameters = array_merge($parameters, $overrides);
    $parameters = array_filter(
        $parameters,
        static fn (mixed $value, string $key): bool => $key === 'page' || $value !== ''
            && $value !== null,
        ARRAY_FILTER_USE_BOTH
    );
    return '?' . http_build_query($parameters);
};
$statusClasses = [
    'PLANNED' => 'calendar-status-planned',
    'IN_PROGRESS' => 'calendar-status-progress',
    'COMPLETED' => 'calendar-status-completed',
    'CANCELLED' => 'calendar-status-cancelled',
];
?>

<section class="calendar-page" aria-labelledby="calendar-title">
    <div class="calendar-heading">
        <div>
            <p class="eyebrow">PLANIFIKIMI I OPERACIONEVE</p>
            <h2 id="calendar-title">Kalendari i transporteve</h2>
            <p>Shiko oraret dhe caktimet e shoferëve e automjeteve.</p>
        </div>
        <a class="button button-primary" href="?page=transports&amp;action=create">＋ Shto transport</a>
    </div>

    <?php if ($calendarData['error'] !== null): ?>
        <div class="notice notice-error" role="alert"><?= $escape($calendarData['error']) ?></div>
    <?php endif; ?>

    <div class="calendar-toolbar">
        <nav class="calendar-period-controls" aria-label="Ndrysho periudhën e kalendarit">
            <a class="button button-secondary" href="<?= $escape($buildCalendarUrl(['date' => $calendarData['previousDate']])) ?>" aria-label="Periudha paraprake">←</a>
            <a class="button button-secondary calendar-today" href="<?= $escape($buildCalendarUrl(['date' => $calendarData['today']])) ?>">Sot</a>
            <a class="button button-secondary" href="<?= $escape($buildCalendarUrl(['date' => $calendarData['nextDate']])) ?>" aria-label="Periudha pasuese">→</a>
        </nav>
        <h3 class="calendar-range">
            <?php if ($calendarData['view'] === 'day'): ?>
                <?= $escape($calendarData['days'][0]['label']) ?>, <?= $escape($calendarData['days'][0]['display_date']) ?>
            <?php else: ?>
                <?= $escape(date('d.m.Y', strtotime($calendarData['periodStart']))) ?>
                – <?= $escape(date('d.m.Y', strtotime($calendarData['periodEnd']))) ?>
            <?php endif; ?>
        </h3>
        <div class="calendar-view-switch" aria-label="Pamja e kalendarit">
            <a class="button <?= $calendarData['view'] === 'day' ? 'button-primary' : 'button-secondary' ?>"
               href="<?= $escape($buildCalendarUrl(['view' => 'day'])) ?>" <?= $calendarData['view'] === 'day' ? 'aria-current="page"' : '' ?>>Ditë</a>
            <a class="button <?= $calendarData['view'] === 'week' ? 'button-primary' : 'button-secondary' ?>"
               href="<?= $escape($buildCalendarUrl(['view' => 'week'])) ?>" <?= $calendarData['view'] === 'week' ? 'aria-current="page"' : '' ?>>Javë</a>
        </div>
    </div>

    <form class="calendar-filters filter-card" method="get" action="?page=calendar">
        <input type="hidden" name="page" value="calendar">
        <input type="hidden" name="view" value="<?= $escape($calendarData['view']) ?>">
        <input type="hidden" name="date" value="<?= $escape($calendarData['selectedDate']) ?>">
        <div class="calendar-filter-field">
            <label for="calendar-status">Statusi</label>
            <select id="calendar-status" name="status">
                <option value="">Të gjitha statuset</option>
                <?php foreach ($calendarData['statuses'] as $value => $label): ?>
                    <option value="<?= $escape($value) ?>" <?= $calendarData['filters']['status'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="calendar-filter-field">
            <label for="calendar-driver">Shoferi</label>
            <select id="calendar-driver" name="driver_id">
                <option value="">Të gjithë shoferët</option>
                <?php foreach ($calendarData['drivers'] as $driver): ?>
                    <option value="<?= (int) $driver['id'] ?>" <?= $calendarData['filters']['driver_id'] === (string) $driver['id'] ? 'selected' : '' ?>><?= $escape($driver['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="calendar-filter-field">
            <label for="calendar-vehicle">Automjeti</label>
            <select id="calendar-vehicle" name="vehicle_id">
                <option value="">Të gjitha automjetet</option>
                <?php foreach ($calendarData['vehicles'] as $vehicle): ?>
                    <option value="<?= (int) $vehicle['id'] ?>" <?= $calendarData['filters']['vehicle_id'] === (string) $vehicle['id'] ? 'selected' : '' ?>><?= $escape($vehicle['plate_number']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="calendar-filter-actions">
            <button class="button button-secondary" type="submit">Filtro</button>
            <a class="calendar-clear-filters" href="<?= $escape($buildCalendarUrl(['status' => '', 'driver_id' => '', 'vehicle_id' => ''])) ?>">Pastro</a>
        </div>
    </form>

    <p class="calendar-result-count" role="status">
        <?= (int) $calendarData['transportCount'] ?>
        <?= $calendarData['transportCount'] === 1 ? 'transport' : 'transporte' ?> në periudhë
    </p>

    <div class="calendar-days<?= $calendarData['view'] === 'day' ? ' calendar-days-single' : '' ?>">
        <?php foreach ($calendarData['days'] as $day): ?>
            <section class="calendar-day<?= $day['is_today'] ? ' calendar-day-today' : '' ?>" aria-labelledby="calendar-day-<?= $escape($day['date']) ?>">
                <header class="calendar-day-heading">
                    <h3 id="calendar-day-<?= $escape($day['date']) ?>">
                        <span><?= $escape($day['label']) ?></span>
                        <time datetime="<?= $escape($day['date']) ?>"><?= $escape($day['display_date']) ?></time>
                    </h3>
                    <span class="calendar-day-count"><?= count($day['transports']) ?></span>
                </header>
                <?php if ($day['transports'] === []): ?>
                    <p class="calendar-empty-day">Nuk ka transporte të planifikuara.</p>
                <?php else: ?>
                    <div class="calendar-event-list">
                        <?php foreach ($day['transports'] as $transport): ?>
                            <?php
                            $statusLabel = $calendarData['statuses'][$transport['status']] ?? $transport['status'];
                            $statusClass = $statusClasses[$transport['status']] ?? '';
                            ?>
                            <a class="calendar-event <?= $escape($statusClass) ?>"
                               href="?page=transports&amp;action=edit&amp;id=<?= (int) $transport['id'] ?>"
                               aria-label="Transporti <?= (int) $transport['id'] ?>: <?= $escape($transport['customer_name']) ?>, <?= $escape(substr($transport['scheduled_date'], 11, 5)) ?>">
                                <span class="calendar-event-time">
                                    <time datetime="<?= $escape($transport['scheduled_date']) ?>"><?= $escape(substr($transport['scheduled_date'], 11, 5)) ?></time>
                                    <span>–</span>
                                    <time datetime="<?= $escape($transport['scheduled_end']) ?>"><?= $escape(substr($transport['scheduled_end'], 11, 5)) ?></time>
                                </span>
                                <strong class="calendar-event-customer"><?= $escape($transport['customer_name']) ?></strong>
                                <span class="calendar-event-cargo"><?= $escape($transport['cargo_description']) ?></span>
                                <span class="calendar-event-route">
                                    <?= $escape($transport['pickup_address']) ?>
                                    <span aria-hidden="true">→</span>
                                    <?= $escape($transport['destination_address']) ?>
                                </span>
                                <span class="calendar-event-assignment">
                                    <?= $escape($transport['driver_name'] ?? 'Pa shofer') ?>
                                    <span aria-hidden="true">·</span>
                                    <?= $escape($transport['plate_number'] ?? 'Pa automjet') ?>
                                </span>
                                <span class="calendar-event-status"><?= $escape($statusLabel) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</section>
