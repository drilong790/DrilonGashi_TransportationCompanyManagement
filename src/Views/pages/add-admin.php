<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="module-toolbar">
    <p>Krijo llogari administratori për një person të besuar. Roli caktohet nga serveri, dhe llogaria aktivizohet pasi pronari verifikon email-in.</p>
    <?php if ($user['role'] === 'SUPERADMIN'): ?>
        <a class="button button-secondary" href="?page=dashboard">Kthehu te paneli</a>
    <?php endif; ?>
</div>

<?php if ($adminSuccess !== null): ?>
    <div class="notice notice-success" role="status"><?= $escape($adminSuccess) ?></div>
<?php endif; ?>

<?php if ($adminError !== null): ?>
    <div class="notice notice-error" role="alert"><?= $escape($adminError) ?></div>
<?php endif; ?>

<?php if (in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true)): ?>
    <form class="record-form auth-admin-form" method="post" action="?page=add-admin">
        <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
        <div class="form-grid">
            <div class="form-field">
                <label for="full_name">Emri i plotë</label>
                <input id="full_name" name="full_name" type="text" autocomplete="name" maxlength="150" value="<?= $escape($adminValues['full_name']) ?>" required autofocus>
            </div>
            <div class="form-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" maxlength="150" value="<?= $escape($adminValues['email']) ?>" required>
            </div>
            <div class="form-field">
                <label for="password">Fjalëkalimi (minimumi 12 karaktere)</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </div>
            <div class="form-field">
                <label for="password_confirmation">Përsërit fjalëkalimin</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
            </div>
        </div>
        <div class="form-actions">
            <button class="button button-primary" type="submit">Krijo llogari ADMIN</button>
        </div>
    </form>
<?php endif; ?>
