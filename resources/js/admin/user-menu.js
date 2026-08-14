export function bootAdminUserMenus() {
    if (window.__catalogHubAdminUserMenusBooted) return;
    window.__catalogHubAdminUserMenusBooted = true;

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) return;

        document.querySelectorAll('[data-central-user-menu][open]').forEach((menu) => {
            if (! menu.contains(event.target)) menu.removeAttribute('open');
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;

        document.querySelectorAll('[data-central-user-menu][open]').forEach((menu) => {
            menu.removeAttribute('open');
            menu.querySelector('summary')?.focus();
        });
    });
}
