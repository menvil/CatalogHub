export function bootAdminFilterDrawers() {
    if (window.__catalogHubAdminFilterDrawersBooted) {
        return;
    }

    window.__catalogHubAdminFilterDrawersBooted = true;

    const close = (drawer) => {
        drawer.classList.add('hidden');
        drawer.dataset.adminFilterOpen = 'false';
        document.body.classList.remove('admin-filter-drawer-open');
    };

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const openButton = event.target.closest('[data-admin-filter-open]');
        const closeButton = event.target.closest('[data-admin-filter-close]');

        if (openButton) {
            const drawer = document.getElementById(openButton.dataset.adminFilterOpen);

            if (drawer) {
                drawer.classList.remove('hidden');
                drawer.dataset.adminFilterOpen = 'true';
                document.body.classList.add('admin-filter-drawer-open');
                drawer.querySelector('input, select, button')?.focus();
            }
        }

        if (closeButton) {
            const drawer = closeButton.closest('[data-admin-filter-drawer]');

            if (drawer) {
                close(drawer);
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        const drawer = document.querySelector('[data-admin-filter-drawer][data-admin-filter-open="true"]');

        if (drawer) {
            close(drawer);
        }
    });
}
