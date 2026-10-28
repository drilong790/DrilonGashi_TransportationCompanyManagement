<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Verifiko email-in | Transporti</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="login-page">
    <main class="login-card">
        <a class="brand login-brand" href="?page=verify-email">
            <span class="brand-mark" aria-hidden="true">TC</span>
            <span class="brand-copy">
                <strong>Transporti</strong>
                <span>Company management</span>
            </span>
        </a>
        <p class="eyebrow">VERIFIKIMI I EMAIL-IT</p>
        <h1>Konfirmo email-in</h1>
        <p class="login-description">Fut kodin 6-shifror që u dërgua në email. Kodi skadon pas 10 minutash.</p>

        <?php if ($verificationMessage !== null): ?>
            <div class="notice notice-success" role="status"><?= $escape($verificationMessage) ?></div>
        <?php endif; ?>
        <?php if ($verificationError !== null): ?>
            <div class="notice notice-error" role="alert"><?= $escape($verificationError) ?></div>
        <?php endif; ?>

        <form class="login-form" method="post" action="?page=verify-email">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="action" value="verify">
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="150" value="<?= $escape($verificationEmail) ?>" required autofocus>
            </div>
            <div class="form-field">
                <label for="code">Kodi i verifikimit</label>
                <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="one-time-code" required>
            </div>
            <button class="button button-primary login-submit" type="submit">Verifiko llogarinë</button>
        </form>

        <form class="login-form" method="post" action="?page=verify-email">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="action" value="resend">
            <div class="form-field">
                <label for="resend-email">Email për ridërgim</label>
                <input id="resend-email" name="email" type="email" autocomplete="email" maxlength="150" value="<?= $escape($verificationEmail) ?>" required>
            </div>
            <button class="button button-secondary login-submit" type="submit">Ridërgo kodin</button>
        </form>
        <p class="auth-switch">E ke verifikuar email-in? <a href="?page=login">Hyr këtu</a></p>
    </main>
</body>
</html>
