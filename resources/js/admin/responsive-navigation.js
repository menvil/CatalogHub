export function bootResponsiveAdminNavigation(config) {
    const initializedShells = [];

    document.querySelectorAll(config.shellSelector).forEach((shell) => {
        const sidebar = shell.querySelector(config.sidebarSelector);
        const openButton = shell.querySelector(config.openSelector);
        const closeButton = shell.querySelector(config.closeSelector);
        const backdrop = shell.querySelector(config.backdropSelector);
        const collapseButton = shell.querySelector(config.collapseSelector);
        const shouldPersist = shell.dataset[config.persistDataset] !== 'false';

        if (! sidebar || ! openButton || ! collapseButton) {
            return;
        }

        let mobileTrigger = null;
        const focusableSelector = 'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])';

        const storedPreference = () => {
            try {
                return window.localStorage.getItem(config.preferenceKey) === 'true';
            } catch {
                return false;
            }
        };

        const persistPreference = (collapsed) => {
            try {
                window.localStorage.setItem(config.preferenceKey, String(collapsed));
            } catch {
                // The shell remains usable when storage is unavailable.
            }
        };

        const setCollapsed = (collapsed) => {
            shell.dataset[config.collapsedDataset] = String(collapsed);
            collapseButton.setAttribute('aria-pressed', String(collapsed));
            collapseButton.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
        };

        const closeMobile = ({ restoreFocus = true } = {}) => {
            sidebar.dataset[config.mobileOpenDataset] = 'false';
            shell.dataset[config.mobileOpenDataset] = 'false';
            openButton.setAttribute('aria-expanded', 'false');
            document.body.classList.remove(config.bodyLockClass);

            if (restoreFocus && mobileTrigger) {
                mobileTrigger.focus({ preventScroll: true });
            }

            mobileTrigger = null;
        };

        const openMobile = () => {
            mobileTrigger = openButton;
            sidebar.dataset[config.mobileOpenDataset] = 'true';
            shell.dataset[config.mobileOpenDataset] = 'true';
            openButton.setAttribute('aria-expanded', 'true');
            document.body.classList.add(config.bodyLockClass);

            const focusTarget = sidebar.querySelector(focusableSelector) ?? sidebar;

            if (focusTarget === sidebar && ! sidebar.hasAttribute('tabindex')) {
                sidebar.setAttribute('tabindex', '-1');
            }

            focusTarget.focus({ preventScroll: true });
        };

        const previewState = shell.dataset[config.previewDataset];
        setCollapsed(previewState ? previewState === 'collapsed' : storedPreference());

        openButton.addEventListener('click', openMobile);
        closeButton?.addEventListener('click', () => closeMobile());
        backdrop?.addEventListener('click', () => closeMobile());
        collapseButton.addEventListener('click', () => {
            const collapsed = shell.dataset[config.collapsedDataset] !== 'true';
            setCollapsed(collapsed);

            if (shouldPersist) {
                persistPreference(collapsed);
            }
        });

        if (config.closeOnNavigateSelector) {
            shell.querySelectorAll(config.closeOnNavigateSelector).forEach((link) => {
                link.addEventListener('click', () => closeMobile({ restoreFocus: false }));
            });
        }

        document.addEventListener('keydown', (event) => {
            if (sidebar.dataset[config.mobileOpenDataset] !== 'true') {
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

            const focusable = Array.from(sidebar.querySelectorAll(focusableSelector))
                .filter((element) => element.getClientRects().length > 0);
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

        if (previewState === 'mobile') {
            openMobile();
        }

        initializedShells.push(shell);
    });

    return initializedShells;
}
