(() => {
    const sidebar = document.querySelector('#primary-sidebar');
    const menuToggle = document.querySelector('.mobile-menu-toggle');

    if (sidebar && menuToggle) {
        menuToggle.addEventListener('click', () => {
            const isOpen = sidebar.classList.toggle('is-open');
            menuToggle.setAttribute('aria-expanded', String(isOpen));
            menuToggle.setAttribute('aria-label', isOpen ? 'Mbyll menynë' : 'Hap menynë');
        });

        sidebar.querySelectorAll('#primary-navigation a').forEach((link) => {
            link.addEventListener('click', () => {
                sidebar.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.setAttribute('aria-label', 'Hap menynë');
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
                sidebar.classList.remove('is-open');
                menuToggle.setAttribute('aria-expanded', 'false');
                menuToggle.focus();
            }
        });
    }

    const confirmationDialog = document.querySelector('.confirm-dialog');
    const confirmationMessage = document.querySelector('#confirm-dialog-message');
    const confirmationButton = document.querySelector('[data-confirm-submit]');
    const cancelConfirmationButton = document.querySelector('[data-confirm-cancel]');
    let pendingForm = null;
    let returnFocus = null;

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }

        if (form.hasAttribute('data-confirm') && form.dataset.confirmed !== 'true') {
            if (
                confirmationDialog
                && confirmationMessage
                && confirmationButton
                && cancelConfirmationButton
                && typeof confirmationDialog.showModal === 'function'
            ) {
                event.preventDefault();
                pendingForm = form;
                returnFocus = event.submitter ?? form.querySelector('button[type="submit"]');
                confirmationMessage.textContent = form.dataset.confirm ?? 'Je i sigurt që dëshiron ta vazhdosh këtë veprim?';
                confirmationDialog.showModal();
                cancelConfirmationButton.focus();
                return;
            }

            if (!window.confirm(form.dataset.confirm ?? 'Je i sigurt që dëshiron ta vazhdosh këtë veprim?')) {
                event.preventDefault();
                return;
            }
        }

        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
        }
        form.setAttribute('aria-busy', 'true');
        form.classList.add('is-loading');
        form.querySelectorAll('button[type="submit"]').forEach((button) => {
            button.disabled = true;
            if (!button.dataset.loadingLabel) {
                button.dataset.loadingLabel = button.textContent.trim();
            }
            button.textContent = button.dataset.loadingText ?? 'Duke ruajtur…';
        });
    });

    if (confirmationDialog && confirmationMessage && confirmationButton && cancelConfirmationButton) {
        confirmationButton.addEventListener('click', () => {
            if (!pendingForm) {
                confirmationDialog.close();
                return;
            }

            const form = pendingForm;
            pendingForm = null;
            form.dataset.confirmed = 'true';
            confirmationDialog.close();
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        });

        cancelConfirmationButton.addEventListener('click', () => {
            confirmationDialog.close();
        });

        confirmationDialog.addEventListener('close', () => {
            if (returnFocus instanceof HTMLElement && returnFocus.isConnected) {
                returnFocus.focus();
            }
            returnFocus = null;
            pendingForm = null;
        });
    }

    const collator = new Intl.Collator('sq', { numeric: true, sensitivity: 'base' });
    document.querySelectorAll('[data-table-enhancement] table').forEach((table, tableIndex) => {
        const body = table.tBodies[0];
        if (!body) {
            return;
        }

        const rows = Array.from(body.rows);
        if (rows.length === 0) {
            return;
        }

        const wrapper = table.closest('[data-table-enhancement]');
        if (!wrapper) {
            return;
        }

        const toolbar = document.createElement('div');
        toolbar.className = 'table-tools';
        const searchId = `table-search-${tableIndex}`;
        const searchLabel = document.createElement('label');
        searchLabel.className = 'table-search-label';
        searchLabel.htmlFor = searchId;
        searchLabel.textContent = 'Kërko në tabelë';

        const search = document.createElement('input');
        search.className = 'table-search-input';
        search.id = searchId;
        search.type = 'search';
        search.placeholder = 'Shkruaj për të filtruar…';
        search.autocomplete = 'off';

        const pageSizeLabel = document.createElement('label');
        pageSizeLabel.className = 'table-page-size-label';
        const pageSizeId = `table-page-size-${tableIndex}`;
        pageSizeLabel.htmlFor = pageSizeId;
        pageSizeLabel.textContent = 'Rreshta për faqe';

        const pageSize = document.createElement('select');
        pageSize.className = 'table-page-size';
        pageSize.id = pageSizeId;
        [
            ['10', '10'],
            ['25', '25'],
            ['50', '50'],
            ['all', 'Të gjithë'],
        ].forEach(([value, label]) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = label;
            pageSize.append(option);
        });

        const count = document.createElement('span');
        count.className = 'table-result-count';
        count.setAttribute('role', 'status');
        count.setAttribute('aria-live', 'polite');
        count.setAttribute('aria-atomic', 'true');

        const searchField = document.createElement('div');
        searchField.className = 'table-search-field';
        searchField.append(searchLabel, search);

        const pageSizeField = document.createElement('div');
        pageSizeField.className = 'table-page-size-field';
        pageSizeField.append(pageSizeLabel, pageSize);
        toolbar.append(searchField, pageSizeField, count);
        wrapper.before(toolbar);

        const pagination = document.createElement('nav');
        pagination.className = 'table-pagination';
        pagination.setAttribute('aria-label', 'Faqëzimi i tabelës');
        const pageSummary = document.createElement('span');
        pageSummary.className = 'table-page-summary';

        const previous = document.createElement('button');
        previous.type = 'button';
        previous.className = 'table-page-button';
        previous.textContent = 'Më parë';
        previous.setAttribute('aria-label', 'Faqja e mëparshme');

        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'table-page-button';
        next.textContent = 'Tjetra';
        next.setAttribute('aria-label', 'Faqja tjetër');
        pagination.append(pageSummary, previous, next);
        wrapper.after(pagination);

        const sortState = { column: -1, direction: 1 };
        Array.from(table.tHead?.rows[0]?.cells ?? []).forEach((header, columnIndex) => {
            if (header.dataset.sortable === 'false') {
                return;
            }
            header.setAttribute('aria-sort', 'none');
            const label = header.textContent.trim();
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'table-sort-button';
            button.textContent = label;
            button.setAttribute('aria-label', `Rendit sipas ${label}`);
            const indicator = document.createElement('span');
            indicator.className = 'table-sort-indicator';
            indicator.setAttribute('aria-hidden', 'true');
            indicator.textContent = '↕';
            button.append(indicator);
            header.textContent = '';
            header.append(button);

            button.addEventListener('click', () => {
                if (sortState.column === columnIndex) {
                    sortState.direction *= -1;
                } else {
                    sortState.column = columnIndex;
                    sortState.direction = 1;
                }

                Array.from(table.tHead.rows[0].cells).forEach((cell, index) => {
                    cell.setAttribute('aria-sort', index === sortState.column
                        ? (sortState.direction === 1 ? 'ascending' : 'descending')
                        : 'none');
                });
                const direction = sortState.direction === 1 ? 'rritës' : 'zbritës';
                button.setAttribute('aria-label', `Rendit sipas ${label} në rend ${direction}`);
                render();
            });
        });

        let currentPage = 0;
        let filteredRows = rows;
        const render = () => {
            const query = search.value.trim().toLocaleLowerCase('sq');
            filteredRows = rows.filter((row) => row.textContent.toLocaleLowerCase('sq').includes(query));

            if (sortState.column >= 0) {
                filteredRows.sort((left, right) => {
                    const leftText = left.cells[sortState.column]?.innerText.trim() ?? '';
                    const rightText = right.cells[sortState.column]?.innerText.trim() ?? '';
                    return collator.compare(leftText, rightText) * sortState.direction;
                });
            }

            const size = pageSize.value === 'all' ? Math.max(filteredRows.length, 1) : Number(pageSize.value);
            const pageCount = Math.max(1, Math.ceil(filteredRows.length / size));
            currentPage = Math.min(currentPage, pageCount - 1);
            const start = currentPage * size;
            const visibleRows = filteredRows.slice(start, start + size);

            rows.forEach((row) => {
                row.hidden = true;
            });
            visibleRows.forEach((row) => {
                row.hidden = false;
                body.append(row);
            });

            count.textContent = filteredRows.length === 0
                ? 'Nuk u gjet asnjë rresht'
                : `${start + 1}–${Math.min(start + size, filteredRows.length)} nga ${filteredRows.length} rreshta`;
            pageSummary.textContent = `Faqja ${currentPage + 1} nga ${pageCount}`;
            previous.disabled = currentPage === 0;
            next.disabled = currentPage >= pageCount - 1;
            pagination.hidden = filteredRows.length <= size;

            let emptyMessage = body.querySelector('[data-table-no-results]');
            if (filteredRows.length === 0 && !emptyMessage) {
                emptyMessage = document.createElement('tr');
                emptyMessage.dataset.tableNoResults = 'true';
                const cell = document.createElement('td');
                cell.colSpan = table.tHead?.rows[0]?.cells.length ?? 1;
                cell.className = 'table-no-results';
                cell.textContent = 'Nuk u gjet asnjë rezultat. Provo një kërkim tjetër.';
                emptyMessage.append(cell);
                body.append(emptyMessage);
            } else if (filteredRows.length > 0) {
                emptyMessage?.remove();
            }
        };

        search.addEventListener('input', () => {
            currentPage = 0;
            render();
        });
        pageSize.addEventListener('change', () => {
            currentPage = 0;
            render();
        });
        previous.addEventListener('click', () => {
            currentPage = Math.max(0, currentPage - 1);
            render();
        });
        next.addEventListener('click', () => {
            currentPage += 1;
            render();
        });
        render();
    });

    const localDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    document.querySelectorAll('[data-date-range-form]').forEach((form) => {
        const start = form.querySelector('[name="date_from"]');
        const end = form.querySelector('[name="date_to"]');
        if (!(start instanceof HTMLInputElement) || !(end instanceof HTMLInputElement)) {
            return;
        }

        form.querySelectorAll('[data-range-days]').forEach((button) => {
            button.addEventListener('click', () => {
                const days = Number(button.dataset.rangeDays);
                if (!Number.isInteger(days) || days < 1) {
                    return;
                }
                const today = new Date();
                const firstDay = new Date(today);
                firstDay.setDate(today.getDate() - days + 1);
                start.value = localDate(firstDay);
                end.value = localDate(today);
                form.requestSubmit();
            });
        });

        form.querySelector('.quick-range-month')?.addEventListener('click', () => {
            const today = new Date();
            start.value = localDate(new Date(today.getFullYear(), today.getMonth(), 1));
            end.value = localDate(today);
            form.requestSubmit();
        });
    });
})();
