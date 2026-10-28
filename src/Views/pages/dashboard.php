<div class="welcome-card">
    <div class="welcome-copy">
        <p class="card-eyebrow">MIRË SE ERDHE</p>
        <h2>Organizo punën e transportit në një vend.</h2>
        <p>
            Paneli lexon përmbledhjen e të dhënave nga databaza e kompanisë.
        </p>
    </div>
    <div class="welcome-art" aria-hidden="true">
        <span class="art-road"></span>
        <span class="art-truck">TC</span>
        <span class="art-route-dot"></span>
    </div>
</div>

<div class="section-heading">
    <div>
        <p class="eyebrow">HAPËSIRA E PUNËS</p>
        <h2>Modulet e sistemit</h2>
    </div>
    <span class="module-count">4 module</span>
</div>

<div class="module-grid">
    <a class="module-card" href="?page=transports">
        <span class="module-icon module-icon-blue" aria-hidden="true">TR</span>
        <span class="module-card-title">Transportet</span>
        <span class="module-card-description">Planifikimi dhe ndjekja e dërgesave.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat"><?= (int) $dashboardStats['transports'] ?> transporte</span>
        <?php endif; ?>
        <span class="module-card-link">Hap modulin <span aria-hidden="true">→</span></span>
    </a>
    <a class="module-card" href="?page=customers">
        <span class="module-icon module-icon-violet" aria-hidden="true">KL</span>
        <span class="module-card-title">Klientët</span>
        <span class="module-card-description">Të dhënat dhe kontaktet e klientëve.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat"><?= (int) $dashboardStats['customers'] ?> klientë</span>
        <?php endif; ?>
        <span class="module-card-link">Hap modulin <span aria-hidden="true">→</span></span>
    </a>
    <a class="module-card" href="?page=drivers">
        <span class="module-icon module-icon-green" aria-hidden="true">SH</span>
        <span class="module-card-title">Shoferët</span>
        <span class="module-card-description">Informacioni dhe disponueshmëria e shoferëve.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat"><?= (int) $dashboardStats['drivers'] ?> shoferë</span>
        <?php endif; ?>
        <span class="module-card-link">Hap modulin <span aria-hidden="true">→</span></span>
    </a>
    <a class="module-card" href="?page=vehicles">
        <span class="module-icon module-icon-orange" aria-hidden="true">AU</span>
        <span class="module-card-title">Automjetet</span>
        <span class="module-card-description">Flota, targat dhe kapacitetet.</span>
        <?php if ($dashboardStats !== null): ?>
            <span class="module-card-stat"><?= (int) $dashboardStats['vehicles'] ?> automjete</span>
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

    <div class="report-status-grid">
        <?php foreach ($dashboardReport as $reportRow): ?>
            <article class="report-status-card">
                <span><?= htmlspecialchars($reportRow['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <strong><?= (int) $reportRow['total'] ?></strong>
                <small>transport<?= (int) $reportRow['total'] === 1 ? '' : 'e' ?></small>
            </article>
        <?php endforeach; ?>
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
