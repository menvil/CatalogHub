export function bootAdminDrawers() {
    document.addEventListener('click', (event) => {
        const closeButton = event.target.closest('[data-admin-drawer-close]');

        if (! closeButton) {
            return;
        }

        const drawer = closeButton.closest('[data-admin-drawer]');

        if (! drawer) {
            return;
        }

        drawer.dataset.adminDrawerOpen = 'false';
        drawer.classList.add('hidden');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[data-admin-drawer][data-admin-drawer-open="true"]').forEach((drawer) => {
            drawer.dataset.adminDrawerOpen = 'false';
            drawer.classList.add('hidden');
        });
    });
}
