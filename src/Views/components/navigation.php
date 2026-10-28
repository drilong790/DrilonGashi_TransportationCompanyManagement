<a class="brand" href="?page=dashboard" aria-label="Transportation Company Management - paneli kryesor">
    <span class="brand-mark" aria-hidden="true">TC</span>
    <span class="brand-copy">
        <strong>Transporti</strong>
        <span>Company management</span>
    </span>
</a>

<p class="navigation-label">MENUJA</p>
<nav>
    <ul class="navigation-list">
        <?php foreach ($navigationItems as $key => $label): ?>
            <li>
                <a
                    class="navigation-link<?= $pageKey === $key ? ' is-active' : '' ?>"
                    href="?page=<?= urlencode($key) ?>"
                    <?= $pageKey === $key ? 'aria-current="page"' : '' ?>
                >
                    <span class="navigation-indicator" aria-hidden="true"></span>
                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

<div class="sidebar-note">
    <span class="sidebar-note-icon" aria-hidden="true">i</span>
    <p>Menaxho klientët, shoferët, automjetet dhe transportet nga modulet përkatëse.</p>
</div>
