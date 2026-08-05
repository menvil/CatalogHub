export function bootAdminFilterDrawers() {
    if (window.__catalogHubAdminFilterDrawersBooted) {
        return;
    }

    window.__catalogHubAdminFilterDrawersBooted = true;
    const openButtonByDrawer = new WeakMap();
    const openDrawers = () => Array.from(document.querySelectorAll('[data-admin-filter-drawer][data-admin-filter-open="true"]'));
    const syncBody = () => document.body.classList.toggle('admin-filter-drawer-open', openDrawers().length > 0);

    const close = (drawer, restoreFocus = true) => {
        drawer.classList.add('hidden');
        drawer.dataset.adminFilterOpen = 'false';
        const openButton = openButtonByDrawer.get(drawer);
        openButtonByDrawer.delete(drawer);
        openButton?.setAttribute('aria-expanded', 'false');
        syncBody();

        if (restoreFocus) {
            openButton?.focus({ preventScroll: true });
        }
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
                openDrawers().filter((candidate) => candidate !== drawer).forEach((candidate) => close(candidate, false));
                openButtonByDrawer.set(drawer, openButton);
                drawer.classList.remove('hidden');
                drawer.dataset.adminFilterOpen = 'true';
                openButton.setAttribute('aria-expanded', 'true');
                syncBody();
                Array.from(drawer.querySelectorAll('input, select, button'))
                    .find((control) => ! control.disabled && control.getClientRects().length > 0)
                    ?.focus({ preventScroll: true });
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

        const drawer = openDrawers().at(-1);

        if (drawer) {
            close(drawer);
        }
    });

    window.addEventListener('resize', () => {
        if (window.matchMedia('(min-width: 48rem)').matches) {
            openDrawers().forEach((drawer) => close(drawer, false));
        }
    });
}
