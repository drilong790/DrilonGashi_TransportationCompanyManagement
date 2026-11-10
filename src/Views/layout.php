<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#07111b">
    <title><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?> | Transporti</title>
    <link rel="stylesheet" href="assets/app.css?v=20261110">
    <link rel="stylesheet" href="assets/app-theme.css?v=20261110">
    <?php if ($pageKey === 'dashboard'): ?>
        <link rel="stylesheet" href="assets/dashboard.css?v=20261110">
    <?php endif; ?>
    <script src="assets/app.js?v=20261110" defer></script>
</head>
<body class="app-page<?= $pageKey === 'dashboard' ? ' dashboard-page' : '' ?>">
    <div class="app-shell">
        <aside class="sidebar" id="primary-sidebar" aria-label="Navigimi kryesor">
            <?php require __DIR__ . '/components/navigation.php'; ?>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">MENAXHIMI I TRANSPORTIT</p>
                    <h1><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="page-description"><?= htmlspecialchars($page['description'], ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($user !== null): ?>
                        <p class="signed-in-user">
                            <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8') ?>
                            · <?= match ($user['role']) {
                                'SUPERADMIN' => 'Superadministrator',
                                'ADMIN' => 'Administrator',
                                default => 'Dispeçer',
                            } ?>
                        </p>
                    <?php endif; ?>
                </div>
                <span class="environment-badge"><span aria-hidden="true"></span> Ambient lokal</span>
            </header>

            <section class="page-content" aria-labelledby="page-content-heading">
                <h2 id="page-content-heading" class="visually-hidden">Përmbajtja e faqes</h2>
                <?php if ($databaseError !== null): ?>
                    <div class="database-error" role="alert">
                        <strong>Shërbimi nuk është i disponueshëm</strong>
                        <p><?= htmlspecialchars($databaseError, ENT_QUOTES, 'UTF-8') ?></p>
                        <span>Detajet teknike janë regjistruar në log-un e PHP-së.</span>
                    </div>
                <?php endif; ?>
                <?php if ($databaseError === null && ($moduleData !== null || in_array($pageKey, ['dashboard', 'not_found', 'add-admin', 'users'], true))): ?>
                    <?php require $viewFile; ?>
                <?php endif; ?>
            </section>

            <footer class="footer">
                <span>Transportation Company Management</span>
                <span>Menaxhim i transportit</span>
            </footer>
        </main>
    </div>
    <dialog class="confirm-dialog" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-message">
        <div class="confirm-dialog-content">
            <span class="confirm-dialog-icon" aria-hidden="true">!</span>
            <h2 id="confirm-dialog-title">Konfirmo veprimin</h2>
            <p id="confirm-dialog-message"></p>
            <div class="confirm-dialog-actions">
                <button class="button button-secondary" type="button" data-confirm-cancel>Anulo</button>
                <button class="button button-danger" type="button" data-confirm-submit>Vazhdo</button>
            </div>
        </div>
    </dialog>
</body>
</html>
