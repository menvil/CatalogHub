export function bootAdminTableSelection() {
    if (window.__catalogHubAdminTableSelectionBooted) {
        return;
    }

    window.__catalogHubAdminTableSelectionBooted = true;

    const sync = (table) => {
        const selected = table.querySelectorAll('[data-admin-row-select]:checked').length;
        const selectVisible = table.querySelector('[data-admin-select-visible]');
        const bulkActions = table.id === '' ? null : document.querySelector(`[data-admin-bulk-actions="${CSS.escape(table.id)}"]`);

        if (selectVisible) {
            const total = table.querySelectorAll('[data-admin-row-select]').length;
            selectVisible.checked = total > 0 && selected === total;
            selectVisible.indeterminate = selected > 0 && selected < total;
        }

        if (bulkActions) {
            bulkActions.querySelector('[data-selected-count]').textContent = String(selected);
            bulkActions.querySelectorAll('[data-bulk-action]').forEach((button) => {
                button.disabled = selected === 0;
            });
        }
    };

    document.addEventListener('change', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const table = event.target.closest('table');

        if (! table) {
            return;
        }

        if (event.target.matches('[data-admin-select-visible]')) {
            table.querySelectorAll('[data-admin-row-select]').forEach((checkbox) => {
                checkbox.checked = event.target.checked;
            });
        }

        if (event.target.matches('[data-admin-select-visible], [data-admin-row-select]')) {
            sync(table);
        }
    });

    document.addEventListener('admin:table-state-changed', () => {
        document.querySelectorAll('table').forEach((table) => {
            table.querySelectorAll('[data-admin-row-select], [data-admin-select-visible]').forEach((checkbox) => {
                checkbox.checked = false;
            });
            sync(table);
        });
    });

    document.querySelectorAll('table').forEach(sync);
}
