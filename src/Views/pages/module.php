<?php

$module = $moduleData['module'];
$formAction = $moduleData['action'];
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>
<div class="module-toolbar">
    <p><?= $escape($module['description']) ?></p>
    <?php if ($formAction === 'list' && $moduleData['canManage']): ?>
        <a class="button button-primary" href="?page=<?= urlencode($pageKey) ?>&amp;action=create">Shto <?= $escape($module['singular']) ?></a>
    <?php elseif ($formAction !== 'list'): ?>
        <a class="button button-secondary" href="?page=<?= urlencode($pageKey) ?>">Kthehu te lista</a>
    <?php endif; ?>
</div>

<?php if ($pageKey === 'transports' && $formAction === 'list'): ?>
    <form class="filter-card" method="get" action="">
        <input type="hidden" name="page" value="transports">
        <div class="filter-grid">
            <div class="form-field filter-search">
                <label for="transport-search">Kërko</label>
                <input id="transport-search" name="search" type="search" maxlength="100" placeholder="Klient, adresë, ngarkesë..." value="<?= $escape($moduleData['filters']['search']) ?>">
            </div>
            <div class="form-field">
                <label for="transport-status">Statusi</label>
                <select id="transport-status" name="status">
                    <option value="">Të gjitha</option>
                    <option value="PLANNED" <?= $moduleData['filters']['status'] === 'PLANNED' ? 'selected' : '' ?>>Planifikuar</option>
                    <option value="IN_PROGRESS" <?= $moduleData['filters']['status'] === 'IN_PROGRESS' ? 'selected' : '' ?>>Në proces</option>
                    <option value="COMPLETED" <?= $moduleData['filters']['status'] === 'COMPLETED' ? 'selected' : '' ?>>Përfunduar</option>
                    <option value="CANCELLED" <?= $moduleData['filters']['status'] === 'CANCELLED' ? 'selected' : '' ?>>Anuluar</option>
                </select>
            </div>
            <div class="form-field">
                <label for="transport-date-from">Nga data</label>
                <input id="transport-date-from" name="date_from" type="date" value="<?= $escape($moduleData['filters']['date_from']) ?>">
            </div>
            <div class="form-field">
                <label for="transport-date-to">Deri më</label>
                <input id="transport-date-to" name="date_to" type="date" value="<?= $escape($moduleData['filters']['date_to']) ?>">
            </div>
            <div class="form-field">
                <label for="transport-customer">Klienti</label>
                <select id="transport-customer" name="customer_id">
                    <option value="">Të gjithë klientët</option>
                    <?php foreach ($moduleData['filterOptions']['customers'] as $customer): ?>
                        <option value="<?= (int) $customer['id'] ?>" <?= $moduleData['filters']['customer_id'] === (string) $customer['id'] ? 'selected' : '' ?>>
                            <?= $escape($customer['company_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-field">
                <label for="transport-driver">Shoferi</label>
                <select id="transport-driver" name="driver_id">
                    <option value="">Të gjithë shoferët</option>
                    <?php foreach ($moduleData['filterOptions']['drivers'] as $driver): ?>
                        <option value="<?= (int) $driver['id'] ?>" <?= $moduleData['filters']['driver_id'] === (string) $driver['id'] ? 'selected' : '' ?>>
                            <?= $escape($driver['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="filter-actions">
            <a class="button button-secondary" href="?page=transports">Pastro filtrat</a>
            <button class="button button-primary" type="submit">Kërko transporte</button>
        </div>
    </form>
<?php endif; ?>

<?php if ($moduleData['message'] !== null): ?>
    <div class="notice notice-success" role="status"><?= $escape($moduleData['message']) ?></div>
<?php endif; ?>

<?php if ($moduleData['errors'] !== []): ?>
    <div class="notice notice-error" role="alert">
        <strong>Kontrollo këto të dhëna:</strong>
        <ul>
            <?php foreach ($moduleData['errors'] as $error): ?>
                <li><?= $escape($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($formAction === 'create' || $formAction === 'edit'): ?>
    <form class="record-form" method="post" action="?page=<?= urlencode($pageKey) ?>">
        <input type="hidden" name="csrf_token" value="<?= $escape($moduleData['csrfToken']) ?>">
        <input type="hidden" name="action" value="<?= $formAction === 'create' ? 'create' : 'update' ?>">
        <?php if ($formAction === 'edit'): ?>
            <input type="hidden" name="id" value="<?= (int) $moduleData['id'] ?>">
        <?php endif; ?>

        <div class="form-grid">
            <?php foreach ($module['fields'] as $name => $field): ?>
                <?php
                $value = $moduleData['values'][$name] ?? '';
                $fieldId = 'field-' . $name;
                ?>
                <div class="form-field<?= $field['type'] === 'textarea' ? ' form-field-wide' : '' ?>">
                    <label for="<?= $escape($fieldId) ?>">
                        <?= $escape($field['label']) ?>
                        <?php if ($field['required']): ?><span aria-hidden="true">*</span><?php endif; ?>
                    </label>
                    <?php if ($field['type'] === 'select'): ?>
                        <select id="<?= $escape($fieldId) ?>" name="<?= $escape($name) ?>" <?= $field['required'] ? 'required' : '' ?>>
                            <?php if (!$field['required']): ?>
                                <option value="">Pa caktuar</option>
                            <?php else: ?>
                                <option value="">Zgjidh...</option>
                            <?php endif; ?>
                            <?php foreach ($moduleData['options'][$name] ?? [] as $option): ?>
                                <option value="<?= $escape($option['value']) ?>" <?= (string) $value === $option['value'] ? 'selected' : '' ?>>
                                    <?= $escape($option['label']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif ($field['type'] === 'textarea'): ?>
                        <textarea id="<?= $escape($fieldId) ?>" name="<?= $escape($name) ?>" rows="4" <?= $field['required'] ? 'required' : '' ?>><?= $escape($value) ?></textarea>
                    <?php else: ?>
                        <input
                            id="<?= $escape($fieldId) ?>"
                            name="<?= $escape($name) ?>"
                            type="<?= $escape($field['type']) ?>"
                            value="<?= $escape($value) ?>"
                            <?= isset($field['max']) ? 'maxlength="' . (int) $field['max'] . '"' : '' ?>
                            <?= isset($field['min']) ? 'min="' . (int) $field['min'] . '"' : '' ?>
                            <?= $field['type'] === 'number' ? 'step="1"' : '' ?>
                            <?= $field['required'] ? 'required' : '' ?>
                        >
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <a class="button button-secondary" href="?page=<?= urlencode($pageKey) ?>">Anulo</a>
            <button class="button button-primary" type="submit"><?= $formAction === 'create' ? 'Ruaj regjistrin' : 'Ruaj ndryshimet' ?></button>
        </div>
    </form>
<?php else: ?>
    <div class="table-card">
        <?php if ($moduleData['rows'] === []): ?>
            <div class="empty-state">
                <strong><?= $pageKey === 'transports' ? 'Nuk u gjet asnjë transport' : 'Nuk ka ende të dhëna' ?></strong>
                <p><?= $pageKey === 'transports' ? 'Ndrysho kriteret e kërkimit ose pastro filtrat.' : 'Shto regjistrin e parë për modulin ' . $escape(mb_strtolower($module['title'])) . '.' ?></p>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($module['columns'] as $label): ?>
                                <th scope="col"><?= $escape($label) ?></th>
                            <?php endforeach; ?>
                            <?php if ($moduleData['canManage']): ?>
                                <th scope="col">Veprimet</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($moduleData['rows'] as $row): ?>
                            <tr>
                                <?php foreach ($module['columns'] as $column => $label): ?>
                                    <td><?= $escape($row[$column] ?? '') ?></td>
                                <?php endforeach; ?>
                                <?php if ($moduleData['canManage']): ?>
                                <td>
                                    <div class="row-actions">
                                        <a class="text-action" href="?page=<?= urlencode($pageKey) ?>&amp;action=edit&amp;id=<?= (int) $row['id'] ?>">Ndrysho</a>
                                        <form method="post" action="?page=<?= urlencode($pageKey) ?>" onsubmit="return confirm('Je i sigurt që dëshiron ta fshish këtë regjistër?')">
                                            <input type="hidden" name="csrf_token" value="<?= $escape($moduleData['csrfToken']) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                            <button class="text-action text-action-danger" type="submit">Fshij</button>
                                        </form>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($moduleData['rows']) === 250): ?>
                <p class="table-limit-note">Shfaqen 250 regjistrimet më të fundit.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if ($pageKey === 'transports'): ?>
        <?php
        $exportParameters = array_filter(
            $moduleData['filters'],
            static fn (string $value): bool => $value !== ''
        );
        $exportParameters['page'] = 'transports';
        $exportParameters['export'] = 'csv';
        ?>
        <div class="report-export-row">
            <span>Eksporti CSV përfshin të gjitha rezultatet që përputhen me filtrat.</span>
            <a class="button button-secondary" href="?<?= $escape(http_build_query($exportParameters)) ?>">Eksporto rezultatet CSV</a>
        </div>
    <?php endif; ?>
<?php endif; ?>
