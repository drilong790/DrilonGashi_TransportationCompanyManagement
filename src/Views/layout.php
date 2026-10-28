<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?> | Transporti</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Navigimi kryesor">
            <?php require __DIR__ . '/components/navigation.php'; ?>
        </aside>

        <main class="main-content">
            <header class="page-header">
                <div>
                    <p class="eyebrow">MENAXHIMI I TRANSPORTIT</p>
                    <h1><?= htmlspecialchars($page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                    <p class="page-description"><?= htmlspecialchars($page['description'], ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <span class="environment-badge"><span aria-hidden="true"></span> Ambient lokal</span>
            </header>

            <section class="page-content" aria-labelledby="page-content-heading">
                <h2 id="page-content-heading" class="visually-hidden">Përmbajtja e faqes</h2>
                <?php require $viewFile; ?>
            </section>

            <footer class="footer">
                <span>Transportation Company Management</span>
                <span>Menaxhim i transportit</span>
            </footer>
        </main>
    </div>
</body>
</html>
