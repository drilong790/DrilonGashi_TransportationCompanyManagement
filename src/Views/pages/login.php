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
    <meta name="theme-color" content="#07111b">
    <title>Hyr në sistem | Transporti</title>
    <link rel="stylesheet" href="assets/app.css?v=20261110">
    <link rel="stylesheet" href="assets/intro.css?v=20261110">
    <link rel="stylesheet" href="assets/login.css?v=20261110">
</head>
<body class="login-page auth-page">
    <a class="skip-link" href="#login-form">Kalo te formulari i hyrjes</a>
    <a class="login-back-link" href="?page=intro">
        <span aria-hidden="true">←</span> Kthehu te faqja hyrëse
    </a>
    <div class="login-route-art" aria-hidden="true">
        <span class="login-route-orbit login-route-orbit-outer"></span>
        <span class="login-route-orbit login-route-orbit-inner"></span>
        <svg viewBox="0 0 620 480">
            <path d="M25 400C120 400 98 310 205 310s90-146 190-146 83 92 200 92"/>
            <circle cx="25" cy="400" r="6"/>
            <circle cx="595" cy="256" r="7"/>
        </svg>
        <span class="login-route-label">ROUTE / TC-01</span>
    </div>
    <main class="login-layout" id="login-form">
        <section class="login-story" aria-labelledby="login-story-title">
            <a class="intro-brand login-intro-brand" href="?page=intro" aria-label="Transporti, faqja hyrëse">
                <span class="intro-brand-mark" aria-hidden="true"><img src="assets/brand-symbol.svg?v=20261110" alt=""></span>
                <span class="intro-brand-copy">
                    <strong>TRANSPORTI</strong>
                    <span>COMPANY MANAGEMENT</span>
                </span>
            </a>
            <div class="login-story-copy">
                <p class="login-story-kicker"><span></span> HAPËSIRA JOTE E PUNËS</p>
                <h1 id="login-story-title">Operacionet<br>në <span>lëvizje.</span></h1>
                <p>Një pamje e qartë për transportet, ekipin dhe flotën. Hyr për të vazhduar rrugëtimin.</p>
            </div>
            <div class="login-story-footer">
                <span class="login-story-status"><i></i> SISTEMI I MENAXHIMIT</span>
                <span>Çdo rrugë, nën kontroll.</span>
            </div>
        </section>

        <section class="login-panel" aria-labelledby="login-title">
            <a class="brand login-brand" href="?page=intro" aria-label="Kthehu te faqja hyrëse">
                <span class="brand-mark" aria-hidden="true"><img src="assets/brand-symbol.svg?v=20261110" alt=""></span>
                <span class="brand-copy">
                    <strong>Transporti</strong>
                    <span>Company management</span>
                </span>
            </a>
            <p class="eyebrow">MIRË SE ERDHE</p>
            <h2 id="login-title">Hyr në sistem</h2>
            <p class="login-description">Identifikohu me llogarinë e kompanisë për të vazhduar.</p>

            <?php if ($loginError !== null || $flashMessage !== null): ?>
                <div class="notice <?= $loginError !== null ? 'notice-error' : 'notice-success' ?>" role="<?= $loginError !== null ? 'alert' : 'status' ?>">
                    <?= $escape($loginError ?? $flashMessage) ?>
                </div>
            <?php endif; ?>

            <form class="login-form" id="login-form" method="post" action="?page=login">
                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                <div class="form-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="username" maxlength="150" required autofocus>
                </div>
                <div class="form-field">
                    <label for="password">Fjalëkalimi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                </div>
                <button class="button button-primary login-submit" type="submit">
                    Hyr në platformë <span aria-hidden="true">↗</span>
                </button>
            </form>
            <p class="auth-switch">Nuk ke llogari? <a href="?page=register">Regjistrohu si dispeçer</a></p>
            <a class="login-mobile-home" href="?page=intro"><span aria-hidden="true">←</span> Kthehu te prezantimi</a>
        </section>
    </main>
</body>
</html>
