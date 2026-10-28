<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="module-toolbar">
    <p>Fshi llogari që nuk kanë krijuar transporte. Llogaria jote nuk mund të fshihet këtu.</p>
    <a class="button button-secondary" href="?page=add-admin">Shto administrator</a>
</div>

<?php if ($accountsSuccess !== null): ?>
    <div class="notice notice-success" role="status"><?= $escape($accountsSuccess) ?></div>
<?php endif; ?>

<?php if ($accountsError !== null): ?>
    <div class="notice notice-error" role="alert"><?= $escape($accountsError) ?></div>
<?php endif; ?>

<?php if ($user['role'] === 'ADMIN'): ?>
    <div class="table-card">
        <?php if ($accounts === []): ?>
            <div class="empty-state">
                <strong>Nuk ka llogari për t'u shfaqur</strong>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Emri</th>
                            <th scope="col">Email</th>
                            <th scope="col">Roli</th>
                            <th scope="col">Gjendja</th>
                            <th scope="col">Transporte të krijuara</th>
                            <th scope="col">Veprimi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($accounts as $account): ?>
                            <?php
                            $isCurrentUser = (int) $account['id'] === (int) $user['id'];
                            $hasTransports = (int) $account['transport_count'] > 0;
                            $isVerified = $account['email_verified_at'] !== null;
                            ?>
                            <tr>
                                <td><?= $escape($account['full_name']) ?></td>
                                <td><?= $escape($account['email']) ?></td>
                                <td><?= $account['role'] === 'ADMIN' ? 'Administrator' : 'Dispeçer' ?></td>
                                <td>
                                    <?= (int) $account['is_active'] === 1 && $isVerified
                                        ? 'Aktive · email i verifikuar'
                                        : 'Në pritje të verifikimit' ?>
                                </td>
                                <td><?= (int) $account['transport_count'] ?></td>
                                <td>
                                    <?php if ($isCurrentUser): ?>
                                        <span>Llogaria jote</span>
                                    <?php elseif ($hasTransports): ?>
                                        <span title="Fshirja do të dëmtonte historikun e transporteve">E mbrojtur nga historiku</span>
                                    <?php else: ?>
                                        <form method="post" action="?page=users" onsubmit="return confirm('Je i sigurt që dëshiron ta fshish llogarinë e këtij përdoruesi? Ky veprim nuk mund të zhbëhet.')">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                            <button class="text-action text-action-danger" type="submit">Fshij llogarinë</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
