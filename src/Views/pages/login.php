<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$flashMessage = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);
?>
<!doctype html>
<html lang="sq">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Hyr në sistem | Transporti</title>
    <link rel="stylesheet" href="assets/app.css">
</head>
<body class="login-page">
    <main class="login-card">
        <a class="brand login-brand" href="?page=login">
            <span class="brand-mark" aria-hidden="true">TC</span>
            <span class="brand-copy">
                <strong>Transporti</strong>
                <span>Company management</span>
            </span>
        </a>
        <p class="eyebrow">MIRË SE ERDHE</p>
        <h1>Hyr në sistem</h1>
        <p class="login-description">Identifikohu me llogarinë e kompanisë për të vazhduar.</p>

        <?php if ($loginError !== null || $flashMessage !== null): ?>
            <div class="notice <?= $loginError !== null ? 'notice-error' : 'notice-success' ?>" role="<?= $loginError !== null ? 'alert' : 'status' ?>">
                <?= $escape($loginError ?? $flashMessage) ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="post" action="?page=login">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="username" maxlength="150" required autofocus>
            </div>
            <div class="form-field">
                <label for="password">Fjalëkalimi</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
            </div>
            <button class="button button-primary login-submit" type="submit">Hyr</button>
        </form>
        <p class="auth-switch">Nuk ke llogari? <a href="?page=register">Regjistrohu si dispeçer</a></p>
    </main>
</body>
</html>
