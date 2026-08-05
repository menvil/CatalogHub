export function bootAdminTabs() {
    if (window.__catalogHubAdminTabsBooted) return;
    window.__catalogHubAdminTabsBooted = true;

    document.addEventListener('keydown', (event) => {
        const tab = event.target.closest('[data-admin-tabs] [role="tab"]');
        if (! tab) return;

        const tabs = Array.from(tab.closest('[data-admin-tabs]').querySelectorAll('[role="tab"]'));
        const current = tabs.indexOf(tab);
        const target = {
            ArrowLeft: tabs[(current - 1 + tabs.length) % tabs.length],
            ArrowRight: tabs[(current + 1) % tabs.length],
            Home: tabs[0],
            End: tabs.at(-1),
        }[event.key];

        if (! target) return;
        event.preventDefault();
        target.focus();
    });
}
