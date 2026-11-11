<?php

$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$formatEuro = static fn (mixed $amount): string => number_format((float) $amount, 2, ',', '.') . ' €';
$selectedTransport = $financeData['selectedTransport'];
?>
<?php if ($financeData['message'] !== null): ?>
    <div class="notice notice-success" role="status"><?= $escape($financeData['message']) ?></div>
<?php endif; ?>

<?php if ($financeData['error'] !== null): ?>
    <div class="notice notice-error" role="alert"><?= $escape($financeData['error']) ?></div>
<?php endif; ?>

<?php if (!$financeData['canManage']): ?>
    <p>Qasja në financa është e kufizuar për administratorët.</p>
<?php else: ?>
    <div class="module-toolbar">
        <p>Regjistro të hyrat dhe shpenzimet për secilin transport. Fitimi llogaritet automatikisht: të hyrat minus shpenzimet.</p>
    </div>

    <?php if ($selectedTransport !== null): ?>
        <section class="table-card finance-detail" aria-labelledby="finance-transport-title">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">TRANSPORTI #<?= (int) $selectedTransport['id'] ?></p>
                    <h2 id="finance-transport-title"><?= $escape($selectedTransport['customer_name']) ?></h2>
                    <p><?= $escape($selectedTransport['scheduled_date']) ?> – <?= $escape($selectedTransport['scheduled_end']) ?></p>
                </div>
                <a class="button button-secondary" href="?page=finances">Kthehu te financat</a>
            </div>

            <form class="record-form" method="post" action="?page=finances&amp;transport_id=<?= (int) $selectedTransport['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $escape($financeData['csrfToken']) ?>">
                <input type="hidden" name="finance_action" value="save_revenue">
                <input type="hidden" name="transport_id" value="<?= (int) $selectedTransport['id'] ?>">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="transport-revenue">Të hyrat nga transporti (€)</label>
                        <input id="transport-revenue" name="revenue" type="number" min="0" max="9999999999.99" step="0.01" value="<?= $escape($selectedTransport['revenue']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label>Shpenzimet gjithsej</label>
                        <output><?= $formatEuro($selectedTransport['total_expenses']) ?></output>
                    </div>
                    <div class="form-field">
                        <label>Fitimi</label>
                        <output><?= $formatEuro($selectedTransport['profit']) ?></output>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="button button-primary" type="submit">Ruaj të hyrat</button>
                </div>
            </form>

            <h3>Regjistro shpenzim</h3>
            <form class="record-form" method="post" action="?page=finances&amp;transport_id=<?= (int) $selectedTransport['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $escape($financeData['csrfToken']) ?>">
                <input type="hidden" name="finance_action" value="add_expense">
                <input type="hidden" name="transport_id" value="<?= (int) $selectedTransport['id'] ?>">
                <div class="form-grid">
                    <div class="form-field">
                        <label for="expense-category">Lloji i shpenzimit</label>
                        <select id="expense-category" name="category" required>
                            <?php foreach ($financeData['categories'] as $value => $label): ?>
                                <option value="<?= $escape($value) ?>" <?= $financeData['expenseValues']['category'] === $value ? 'selected' : '' ?>><?= $escape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="expense-description">Përshkrimi</label>
                        <input id="expense-description" name="description" type="text" maxlength="255" value="<?= $escape($financeData['expenseValues']['description']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="expense-amount">Shuma (€)</label>
                        <input id="expense-amount" name="amount" type="number" min="0" max="9999999999.99" step="0.01" value="<?= $escape($financeData['expenseValues']['amount']) ?>" required>
                    </div>
                    <div class="form-field">
                        <label for="expense-date">Data</label>
                        <input id="expense-date" name="expense_date" type="date" value="<?= $escape($financeData['expenseValues']['expense_date']) ?>" required>
                    </div>
                </div>
                <div class="form-actions">
                    <button class="button button-primary" type="submit">Shto shpenzimin</button>
                </div>
            </form>

            <h3>Shpenzimet e regjistruara</h3>
            <?php if ($financeData['expenses'] === []): ?>
                <p class="empty-state">Nuk ka ende shpenzime për këtë transport.</p>
            <?php else: ?>
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr><th>Lloji</th><th>Përshkrimi</th><th>Data</th><th>Shuma</th><th>Veprimet</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($financeData['expenses'] as $expense): ?>
                                <tr>
                                    <td><?= $escape($financeData['categories'][$expense['category']] ?? $expense['category']) ?></td>
                                    <td><?= $escape($expense['description']) ?></td>
                                    <td><?= $escape($expense['expense_date']) ?></td>
                                    <td><?= $formatEuro($expense['amount']) ?></td>
                                    <td>
                                        <form method="post" action="?page=finances&amp;transport_id=<?= (int) $selectedTransport['id'] ?>" data-confirm="Je i sigurt që dëshiron ta fshish këtë shpenzim?">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($financeData['csrfToken']) ?>">
                                            <input type="hidden" name="finance_action" value="delete_expense">
                                            <input type="hidden" name="transport_id" value="<?= (int) $selectedTransport['id'] ?>">
                                            <input type="hidden" name="expense_id" value="<?= (int) $expense['id'] ?>">
                                            <button class="text-action text-action-danger" type="submit">Fshij</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <div class="table-card">
            <?php if ($financeData['transports'] === []): ?>
                <div class="empty-state"><strong>Nuk ka ende transporte</strong><p>Shto një transport që të regjistrosh të hyrat dhe shpenzimet.</p></div>
            <?php else: ?>
                <div class="table-scroll" data-table-enhancement>
                    <table>
                        <thead><tr><th>Transporti</th><th>Data</th><th>Statusi</th><th>Të hyrat</th><th>Shpenzimet</th><th>Fitimi</th><th>Veprimet</th></tr></thead>
                        <tbody>
                            <?php foreach ($financeData['transports'] as $transport): ?>
                                <tr>
                                    <td>#<?= (int) $transport['id'] ?> · <?= $escape($transport['customer_name']) ?></td>
                                    <td><?= $escape($transport['scheduled_date']) ?></td>
                                    <td><?= $escape($transport['status']) ?></td>
                                    <td><?= $formatEuro($transport['revenue']) ?></td>
                                    <td><?= $formatEuro($transport['total_expenses']) ?></td>
                                    <td><?= $formatEuro($transport['profit']) ?></td>
                                    <td><a class="text-action" href="?page=finances&amp;transport_id=<?= (int) $transport['id'] ?>">Menaxho financat</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
