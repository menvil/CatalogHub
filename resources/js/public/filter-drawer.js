export function bootFilterDrawers(root = document) {
    root.querySelectorAll('[data-filter-drawer-open]').forEach((trigger) => {
        if (trigger.dataset.filterDrawerBound === 'true') {
            return;
        }

        trigger.dataset.filterDrawerBound = 'true';
        const drawer = document.getElementById(trigger.getAttribute('aria-controls'));

        if (!(drawer instanceof HTMLDialogElement)) {
            return;
        }

        trigger.addEventListener('click', () => drawer.showModal());
        drawer.querySelectorAll('[data-filter-drawer-close]').forEach((close) => {
            close.addEventListener('click', () => drawer.close());
        });
        drawer.addEventListener('click', (event) => {
            if (event.target === drawer) {
                drawer.close();
            }
        });
    });
}
