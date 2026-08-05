const preferenceKey = 'cataloghub.central.sidebar.collapsed';

export function bootCentralNavigation() {
    document.querySelectorAll('[data-central-shell]').forEach((shell) => {
        const sidebar = shell.querySelector('[data-central-sidebar]');
        const openButton = shell.querySelector('[data-central-sidebar-open]');
        const closeButton = shell.querySelector('[data-central-sidebar-close]');
        const backdrop = shell.querySelector('[data-central-sidebar-backdrop]');
        const collapseButton = shell.querySelector('[data-central-sidebar-collapse]');

        if (! sidebar || ! openButton || ! collapseButton) {
            return;
        }

        let mobileTrigger = null;
        const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

        const storedPreference = () => {
            try {
                return window.localStorage.getItem(preferenceKey) === 'true';
            } catch {
                return false;
            }
        };

        const persistPreference = (collapsed) => {
            try {
                window.localStorage.setItem(preferenceKey, String(collapsed));
            } catch {
                // The shell remains usable when storage is unavailable.
            }
        };

        const setCollapsed = (collapsed) => {
            shell.dataset.centralSidebarCollapsed = String(collapsed);
            collapseButton.setAttribute('aria-pressed', String(collapsed));
            collapseButton.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
        };

        const closeMobile = ({ restoreFocus = true } = {}) => {
            sidebar.dataset.centralSidebarMobileOpen = 'false';
            openButton.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('overflow-hidden');

            if (restoreFocus && mobileTrigger) {
                mobileTrigger.focus({ preventScroll: true });
            }

            mobileTrigger = null;
        };

        const openMobile = () => {
            mobileTrigger = openButton;
            sidebar.dataset.centralSidebarMobileOpen = 'true';
            openButton.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');

            const focusTarget = sidebar.querySelector(focusableSelector) ?? sidebar;
            focusTarget.focus({ preventScroll: true });
        };

        setCollapsed(storedPreference());

        openButton.addEventListener('click', openMobile);
        closeButton?.addEventListener('click', () => closeMobile());
        backdrop?.addEventListener('click', () => closeMobile());
        collapseButton.addEventListener('click', () => {
            const collapsed = shell.dataset.centralSidebarCollapsed !== 'true';
            setCollapsed(collapsed);
            persistPreference(collapsed);
        });

        document.addEventListener('keydown', (event) => {
            if (sidebar.dataset.centralSidebarMobileOpen !== 'true') {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                closeMobile();
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            const focusable = Array.from(sidebar.querySelectorAll(focusableSelector));
            const first = focusable.at(0);
            const last = focusable.at(-1);

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last?.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first?.focus();
            }
        });

        window.addEventListener('resize', () => {
            if (window.matchMedia('(min-width: 64rem)').matches) {
                closeMobile({ restoreFocus: false });
            }
        });
    });
}
