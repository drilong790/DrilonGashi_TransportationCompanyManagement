<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="module-toolbar">
    <p>
        <?php if ($user['role'] === 'SUPERADMIN'): ?>
            Mund të ndryshosh llogaritë ADMIN/DISPATCHER/DRIVER dhe të krijosh hyrje për shoferët. Llogaria jote nuk mund të fshihet; llogaritë me transporte të krijuara janë të mbrojtura.
        <?php else: ?>
            Menaxho emailin/fjalëkalimin e dispeçerëve dhe shoferëve, dhe krijo hyrje për shoferët.
        <?php endif; ?>
    </p>
    <a class="button button-secondary" href="?page=add-admin">Shto administrator</a>
</div>

<?php if ($accountsSuccess !== null): ?>
    <div class="notice notice-success" role="status"><?= $escape($accountsSuccess) ?></div>
<?php endif; ?>

<?php if ($accountsError !== null): ?>
    <div class="notice notice-error" role="alert"><?= $escape($accountsError) ?></div>
<?php endif; ?>

<section class="filter-card" aria-labelledby="driver-account-heading">
    <div class="section-heading">
        <div>
            <p class="eyebrow">APLIKACIONI MOBIL</p>
            <h2 id="driver-account-heading">Krijo llogari për shofer</h2>
        </div>
    </div>
    <?php if ($availableDrivers === []): ?>
        <p class="module-toolbar">Nuk ka shoferë aktivë me email të lirë. Shto email te moduli Shoferët dhe provo përsëri.</p>
    <?php else: ?>
        <form class="record-form" method="post" action="?page=users">
            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
            <input type="hidden" name="account_action" value="create_driver">
            <div class="form-field">
                <label for="driver-account-id">Shoferi</label>
                <select id="driver-account-id" name="driver_id" required>
                    <option value="">Zgjidh shoferin</option>
                    <?php foreach ($availableDrivers as $driver): ?>
                        <option value="<?= (int) $driver['id'] ?>">
                            <?= $escape($driver['full_name']) ?> · <?= $escape($driver['email']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="driver-account-password">Fjalëkalimi i përkohshëm</label>
                <input id="driver-account-password" type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required>
            </div>
            <div class="form-field">
                <label for="driver-account-confirmation">Përsërit fjalëkalimin</label>
                <input id="driver-account-confirmation" type="password" name="password_confirmation" minlength="12" maxlength="72" autocomplete="new-password" required>
            </div>
            <button class="button button-primary" type="submit">Krijo llogarinë</button>
        </form>
    <?php endif; ?>
</section>

<?php if ($accounts === []): ?>
    <div class="table-card">
        <div class="empty-state">
            <strong>Nuk ka llogari në këtë grup për t'u menaxhuar</strong>
        </div>
    </div>
<?php else: ?>
<?php if (in_array($user['role'], ['SUPERADMIN', 'ADMIN'], true)): ?>
    <div class="table-card">
            <div class="table-scroll" data-table-enhancement>
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Emri</th>
                            <th scope="col">Email</th>
                            <th scope="col">Roli</th>
                            <th scope="col">Gjendja</th>
                            <th scope="col">Transporte të krijuara</th>
                            <th scope="col" data-sortable="false">Menaxhimi</th>
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
                                <td><?= match ($account['role']) {
                                    'SUPERADMIN' => 'Superadministrator',
                                    'ADMIN' => 'Administrator',
                                    'DRIVER' => 'Shofer',
                                    default => 'Dispeçer',
                                } ?></td>
                                <td>
                                    <?= (int) $account['is_active'] === 1 && $isVerified
                                        ? 'Aktive · email i verifikuar'
                                        : 'Në pritje të verifikimit' ?>
                                </td>
                                <td><?= (int) $account['transport_count'] ?></td>
                                <td>
                                    <div class="account-management-actions">
                                        <form class="account-management-form" method="post" action="?page=users">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                            <input type="hidden" name="account_action" value="update_email">
                                            <label class="visually-hidden" for="email-<?= (int) $account['id'] ?>">Email i ri për <?= $escape($account['full_name']) ?></label>
                                            <input id="email-<?= (int) $account['id'] ?>" type="email" name="email" value="<?= $escape($account['email']) ?>" maxlength="150" required>
                                            <button class="button button-secondary" type="submit">Ndrysho email</button>
                                        </form>
                                        <form class="account-management-form" method="post" action="?page=users">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                            <input type="hidden" name="account_action" value="reset_password">
                                            <label class="visually-hidden" for="password-<?= (int) $account['id'] ?>">Fjalëkalim i ri për <?= $escape($account['full_name']) ?></label>
                                            <input id="password-<?= (int) $account['id'] ?>" type="password" name="password" minlength="12" maxlength="72" placeholder="Fjalëkalim i ri" required>
                                            <label class="visually-hidden" for="password-confirm-<?= (int) $account['id'] ?>">Përsërit fjalëkalimin e ri për <?= $escape($account['full_name']) ?></label>
                                            <input id="password-confirm-<?= (int) $account['id'] ?>" type="password" name="password_confirmation" minlength="12" maxlength="72" placeholder="Përsërit fjalëkalimin" required>
                                            <button class="button button-secondary" type="submit">Ndrysho fjalëkalimin</button>
                                        </form>
                                        <?php if ($isCurrentUser): ?>
                                            <span>Llogaria jote nuk mund të fshihet.</span>
                                        <?php elseif ($hasTransports): ?>
                                            <span title="Fshirja do të dëmtonte historikun e transporteve">Fshirja bllokohet nga historiku i transporteve.</span>
                                        <?php else: ?>
                                            <form method="post" action="?page=users" data-confirm="Je i sigurt që dëshiron ta fshish këtë llogari? Ky veprim nuk mund të zhbëhet.">
                                                <input type="hidden" name="csrf_token" value="<?= $escape($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                                                <input type="hidden" name="account_action" value="delete">
                                                <button class="text-action text-action-danger" type="submit">Fshij llogarinë</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </div>
<?php endif; ?>
<?php endif; ?>
