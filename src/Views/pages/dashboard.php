<?php
$dashboardName = explode(' ', trim((string) ($user['full_name'] ?? '')), 2)[0] ?? '';
$dashboardNow = new DateTimeImmutable();
?>
<section class="dashboard-hero" aria-labelledby="dashboard-welcome">
    <div class="dashboard-hero-copy">
        <p class="dashboard-overline"><span></span> QENDRA E OPERACIONEVE <span class="hero-date"><?= htmlspecialchars($dashboardNow->format('d.m.Y'), ENT_QUOTES, 'UTF-8') ?></span></p>
        <h2 id="dashboard-welcome">Rrugët nën kontroll<span>.</span></h2>
        <p class="dashboard-hero-description">
            <?= $dashboardName !== '' ? 'Mirë se u ktheve, ' . htmlspecialchars($dashboardName, ENT_QUOTES, 'UTF-8') . '. ' : '' ?>
            Ndiq transportet, ekipin dhe flotën nga një pamje e vetme.
        </p>
        <div class="dashboard-hero-actions">
            <a class="dashboard-primary-action" href="?page=transports&amp;action=create">
                <span aria-hidden="true">＋</span> Regjistro transport
            </a>
            <a class="dashboard-secondary-action" href="?page=transports">
                Shiko të gjitha <span aria-hidden="true">↗</span>
            </a>
        </div>
    </div>
    <div class="hero-route-art" aria-hidden="true">
        <span class="route-orbit route-orbit-one"></span>
        <span class="route-orbit route-orbit-two"></span>
        <span class="route-point route-point-start"></span>
        <span class="route-point route-point-end"></span>
        <span class="route-line"></span>
        <span class="route-truck">TC</span>
        <span class="route-city route-city-start">TIRANË</span>
        <span class="route-city route-city-end">PRISHTINË</span>
        <span class="route-distance">238 <small>KM</small></span>
    </div>
    <div class="hero-total">
        <span>Transportet gjithsej</span>
        <strong><?= $dashboardStats !== null ? (int) $dashboardStats['transports'] : 0 ?></strong>
        <small>në sistem</small>
    </div>
</section>

<section class="dashboard-metrics" aria-label="Përmbledhje e regjistrimeve">
    <article class="metric-card metric-transport">
        <span class="metric-icon" aria-hidden="true">↗</span>
        <span class="metric-label">TRANSPORTE</span>
        <strong><?= $dashboardStats !== null ? (int) $dashboardStats['transports'] : 0 ?></strong>
        <span class="metric-footnote">udhëtime të regjistruara</span>
        <span class="metric-spark metric-spark-blue" aria-hidden="true"></span>
    </article>
    <article class="metric-card metric-customer">
        <span class="metric-icon" aria-hidden="true">◎</span>
        <span class="metric-label">KLIENTË</span>
        <strong><?= $dashboardStats !== null ? (int) $dashboardStats['customers'] : 0 ?></strong>
        <span class="metric-footnote">partnerë të regjistruar</span>
        <span class="metric-spark metric-spark-violet" aria-hidden="true"></span>
    </article>
    <article class="metric-card metric-driver">
        <span class="metric-icon" aria-hidden="true">◉</span>
        <span class="metric-label">SHOFERË</span>
        <strong><?= $dashboardStats !== null ? (int) $dashboardStats['drivers'] : 0 ?></strong>
        <span class="metric-footnote">anëtarë të ekipit</span>
        <span class="metric-spark metric-spark-green" aria-hidden="true"></span>
    </article>
    <article class="metric-card metric-vehicle">
        <span class="metric-icon" aria-hidden="true">▰</span>
        <span class="metric-label">AUTOMJETE</span>
        <strong><?= $dashboardStats !== null ? (int) $dashboardStats['vehicles'] : 0 ?></strong>
        <span class="metric-footnote">mjete në flotë</span>
        <span class="metric-spark metric-spark-orange" aria-hidden="true"></span>
    </article>
</section>

<div class="section-heading dashboard-section-heading">
    <div>
        <p class="eyebrow">QASJE E SHPEJTË</p>
        <h2>Menaxho biznesin</h2>
    </div>
    <span class="module-count">4 module</span>
</div>

<div class="module-grid">
    <a class="module-card" href="?page=transports">
        <span class="module-icon module-icon-blue" aria-hidden="true">TR</span>
        <span class="module-card-title">Transportet</span>
        <span class="module-card-description">Planifikimi dhe ndjekja e dërgesave.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat">Hap itineraret dhe statuset</span>
        <?php endif; ?>
        <span class="module-card-link">Hap modulin <span aria-hidden="true">→</span></span>
    </a>
    <a class="module-card" href="?page=customers">
        <span class="module-icon module-icon-violet" aria-hidden="true">KL</span>
        <span class="module-card-title">Klientët</span>
        <span class="module-card-description">Të dhënat dhe kontaktet e klientëve.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat">Shiko kontaktet dhe porositë</span>
        <?php endif; ?>
        <span class="module-card-link">Hap modulin <span aria-hidden="true">→</span></span>
    </a>
    <a class="module-card" href="?page=drivers">
        <span class="module-icon module-icon-green" aria-hidden="true">SH</span>
        <span class="module-card-title">Shoferët</span>
        <span class="module-card-description">Informacioni dhe disponueshmëria e shoferëve.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat">Menaxho ekipin e shoferëve</span>
        <?php endif; ?>
        <span class="module-card-link">Hap modulin <span aria-hidden="true">→</span></span>
    </a>
    <a class="module-card" href="?page=vehicles">
        <span class="module-icon module-icon-orange" aria-hidden="true">AU</span>
        <span class="module-card-title">Automjetet</span>
        <span class="module-card-description">Flota, targat dhe kapacitetet.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat">Kontrollo flotën dhe kapacitetin</span>
        <?php endif; ?>
        <span class="module-card-link">Hap modulin <span aria-hidden="true">→</span></span>
    </a>
</div>

<section class="transport-report" aria-labelledby="transport-report-title">
    <div class="section-heading">
        <div>
            <p class="eyebrow">RAPORTIMI</p>
            <h2 id="transport-report-title">Raporti i transporteve</h2>
        </div>
        <span class="module-count"><?= (int) $dashboardReportTotal ?> gjithsej</span>
    </div>

    <?php if ($dashboardReportError !== null): ?>
        <div class="notice notice-error" role="alert"><?= htmlspecialchars($dashboardReportError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form class="filter-card report-filter" method="get" action="">
        <input type="hidden" name="page" value="dashboard">
        <div class="report-filter-fields">
            <div class="form-field">
                <label for="report-date-from">Nga data e planifikuar</label>
                <input id="report-date-from" name="date_from" type="date" value="<?= htmlspecialchars($dashboardReportFrom, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-field">
                <label for="report-date-to">Deri më</label>
                <input id="report-date-to" name="date_to" type="date" value="<?= htmlspecialchars($dashboardReportTo, ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>
        <div class="filter-actions">
            <button class="button button-primary" type="submit">Gjenero raportin</button>
            <?php if ($dashboardReportError === null): ?>
                <a class="button button-secondary" href="?page=dashboard&amp;export=csv&amp;date_from=<?= rawurlencode($dashboardReportFrom) ?>&amp;date_to=<?= rawurlencode($dashboardReportTo) ?>">Eksporto CSV</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="report-visual">
        <div class="report-visual-heading">
            <div>
                <span class="report-visual-label">VOLUMI SIPAS STATUSIT</span>
                <strong><?= (int) $dashboardReportTotal ?> <small>transporte në periudhë</small></strong>
            </div>
            <span class="report-visual-mark" aria-hidden="true">↗</span>
        </div>
        <div class="report-bars">
            <?php foreach ($dashboardReport as $reportRow): ?>
                <?php $barWidth = $dashboardReportTotal > 0 ? (int) round(((int) $reportRow['total'] / $dashboardReportTotal) * 100) : 0; ?>
                <div class="report-bar-row">
                    <div class="report-bar-meta">
                        <span class="report-bar-name"><i class="report-legend report-legend-<?= htmlspecialchars(strtolower($reportRow['status']), ENT_QUOTES, 'UTF-8') ?>"></i><?= htmlspecialchars($reportRow['label'], ENT_QUOTES, 'UTF-8') ?></span>
                        <strong><?= (int) $reportRow['total'] ?><small> · <?= $barWidth ?>%</small></strong>
                    </div>
                    <div class="report-bar-track" role="progressbar" aria-label="<?= htmlspecialchars($reportRow['label'], ENT_QUOTES, 'UTF-8') ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $barWidth ?>">
                        <span class="report-bar-fill report-bar-fill-<?= htmlspecialchars(strtolower($reportRow['status']), ENT_QUOTES, 'UTF-8') ?>" style="--bar-width: <?= $barWidth ?>%"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="report-period">
        Periudha: <?= htmlspecialchars($dashboardReportFrom, ENT_QUOTES, 'UTF-8') ?>
        — <?= htmlspecialchars($dashboardReportTo, ENT_QUOTES, 'UTF-8') ?>
    </p>
</section>

<div class="progress-note">
    <span class="progress-mark" aria-hidden="true">✓</span>
    <div>
        <strong>Lidhja me databazën është aktive</strong>
        <p>Paneli tregon regjistrimet dhe modulet janë të mbrojtura sipas rolit të përdoruesit të kyçur.</p>
    </div>
</div>
