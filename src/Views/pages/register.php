<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Krijo llogari | Transporti</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="login-page">
    <main class="login-card">
        <a class="brand login-brand" href="?page=register">
            <span class="brand-mark" aria-hidden="true">TC</span>
            <span class="brand-copy">
                <strong>Transporti</strong>
                <span>Company management</span>
            </span>
        </a>
        <p class="eyebrow">REGJISTRIMI</p>
        <h1>Krijo llogari</h1>
        <p class="login-description">Regjistrimi publik krijon vetëm llogari dispeçeri. Administratori krijohet veçmas nga terminali.</p>

        <?php if ($registerError !== null): ?>
            <div class="notice notice-error" role="alert"><?= $escape($registerError) ?></div>
        <?php endif; ?>

        <form class="login-form" method="post" action="?page=register">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <div class="form-field">
                <label for="full_name">Emri i plotë</label>
                <input id="full_name" name="full_name" type="text" autocomplete="name" maxlength="150" value="<?= $escape($registerValues['full_name']) ?>" required autofocus>
            </div>
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="150" value="<?= $escape($registerValues['email']) ?>" required>
            </div>
            <div class="form-field">
                <label for="password">Fjalëkalimi (minimumi 12 karaktere)</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </div>
            <div class="form-field">
                <label for="password_confirmation">Përsërit fjalëkalimin</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </div>
            <button class="button button-primary login-submit" type="submit">Krijo llogari dispeçeri</button>
        </form>
        <p class="auth-switch">Ke llogari? <a href="?page=login">Hyr këtu</a></p>
    </main>
</body>
</html>
