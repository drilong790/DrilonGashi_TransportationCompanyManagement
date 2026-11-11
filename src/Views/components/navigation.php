<div class="sidebar-brand-row">
    <a class="brand" href="?page=dashboard" aria-label="Transportation Company Management - paneli kryesor">
        <span class="brand-mark" aria-hidden="true"><img src="assets/brand-symbol.svg?v=20261110" alt=""></span>
        <span class="brand-copy">
            <strong>Transporti</strong>
            <span>Company management</span>
        </span>
    </a>
    <button
        class="mobile-menu-toggle"
        type="button"
        aria-expanded="false"
        aria-controls="primary-navigation"
        aria-label="Hap menynë"
    >
        <span aria-hidden="true">☰</span>
        <span>Menuja</span>
    </button>
</div>

<p class="navigation-label">MENUJA</p>
<nav id="primary-navigation" aria-label="Faqet e aplikacionit">
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
        <?php if (in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true)): ?>
            <li>
                <a
                    class="navigation-link<?= $pageKey === 'finances' ? ' is-active' : '' ?>"
                    href="?page=finances"
                    <?= $pageKey === 'finances' ? 'aria-current="page"' : '' ?>
                >
                    <span class="navigation-indicator" aria-hidden="true"></span>
                    <span>Shpenzimet dhe fitimi</span>
                </a>
            </li>
            <li>
                <a
                    class="navigation-link<?= $pageKey === 'add-admin' ? ' is-active' : '' ?>"
                    href="?page=add-admin"
                    <?= $pageKey === 'add-admin' ? 'aria-current="page"' : '' ?>
                >
                    <span class="navigation-indicator" aria-hidden="true"></span>
                    <span>Shto administrator</span>
                </a>
            </li>
            <li>
                <a
                    class="navigation-link<?= $pageKey === 'users' ? ' is-active' : '' ?>"
                    href="?page=users"
                    <?= $pageKey === 'users' ? 'aria-current="page"' : '' ?>
                >
                    <span class="navigation-indicator" aria-hidden="true"></span>
                    <span><?= $user['role'] === 'SUPERADMIN' ? 'Menaxho llogaritë' : 'Menaxho dispeçerët' ?></span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</nav>

<div class="sidebar-note">
    <div class="session-user">
        <strong><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?></strong>
        <span><?= match ($user['role']) {
            'SUPERADMIN' => 'Superadministrator',
            'ADMIN' => 'Administrator',
            default => 'Dispeçer',
        } ?></span>
        <?php if ($user['role'] === 'DISPATCHER'): ?>
            <span>Modulet bazë janë vetëm për shikim.</span>
        <?php endif; ?>
        <form method="post" action="?page=logout">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Dil nga llogaria</button>
        </form>
    </div>
</div>
