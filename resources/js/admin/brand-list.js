export function bootBrandList() {
    if (window.__catalogHubBrandListBooted) return;
    window.__catalogHubBrandListBooted = true;

    let searchTimer;

    const closeActionMenus = (except = null) => {
        document.querySelectorAll('[data-admin-row-actions-menu][open]').forEach((menu) => {
            if (menu !== except) menu.removeAttribute('open');
        });
    };

    const submitSearch = (input, immediate = false) => {
        window.clearTimeout(searchTimer);

        if (immediate) {
            input.form?.requestSubmit();
            return;
        }

        searchTimer = window.setTimeout(() => input.form?.requestSubmit(), 300);
    };

    document.addEventListener('change', (event) => {
        if (! (event.target instanceof HTMLSelectElement)) return;
        if (! event.target.closest('.brand-list-filters, .brand-list-per-page')) return;

        event.target.form?.requestSubmit();
    });

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) return;
        const menu = event.target.closest('[data-admin-row-actions-menu]');
        closeActionMenus(menu);
    });

    document.addEventListener('keydown', (event) => {
        if (! (event.target instanceof Element)) return;
        const menu = event.target.closest('[data-admin-row-actions-menu]');
        if (! menu) return;

        const items = Array.from(menu.querySelectorAll('[role="menuitem"]'));
        const current = items.indexOf(event.target.closest('[role="menuitem"]'));

        if (event.key === 'Escape') {
            event.preventDefault();
            menu.removeAttribute('open');
            menu.querySelector('summary')?.focus();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (! menu.open) menu.open = true;
            const offset = event.key === 'ArrowDown' ? 1 : -1;
            const index = current < 0 ? (offset > 0 ? 0 : items.length - 1) : (current + offset + items.length) % items.length;
            items[index]?.focus();
        }
    });

    document.addEventListener('input', (event) => {
        if (! (event.target instanceof HTMLInputElement)) return;
        if (! event.target.matches('[data-brand-list-search]') || event.isComposing) return;

        submitSearch(event.target, event.target.value === '');
    });

    document.addEventListener('search', (event) => {
        if (! (event.target instanceof HTMLInputElement)) return;
        if (! event.target.matches('[data-brand-list-search]')) return;

        submitSearch(event.target, true);
    });
}
