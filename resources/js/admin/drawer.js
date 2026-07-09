export function bootAdminDrawers() {
    if (window.__catalogHubAdminDrawersBooted) {
        return;
    }

    window.__catalogHubAdminDrawersBooted = true;

    const previousFocusByDrawer = new WeakMap();
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    const openDrawers = () => Array.from(document.querySelectorAll('[data-admin-drawer][data-admin-drawer-open="true"]'));
    const activeDrawer = () => openDrawers().at(-1);
    const focusableElements = (drawer) => Array.from(drawer.querySelectorAll(focusableSelector))
        .filter((element) => element.offsetParent !== null || element === document.activeElement);

    const syncBodyScroll = () => {
        const hasBlockingDrawer = openDrawers().some((drawer) => drawer.dataset.adminDrawerContained !== 'true');
        document.body.classList.toggle('overflow-hidden', hasBlockingDrawer);
    };

    const focusDrawer = (drawer) => {
        if (! previousFocusByDrawer.has(drawer) && ! drawer.contains(document.activeElement)) {
            previousFocusByDrawer.set(drawer, document.activeElement);
        }

        const focusTarget = focusableElements(drawer)[0] ?? drawer.querySelector('[role="dialog"]') ?? drawer;
        focusTarget.setAttribute('tabindex', focusTarget.getAttribute('tabindex') ?? '-1');
        focusTarget.focus({ preventScroll: true });
    };

    const closeDrawer = (drawer) => {
        drawer.dataset.adminDrawerOpen = 'false';
        drawer.classList.add('hidden');

        const previousFocus = previousFocusByDrawer.get(drawer);
        previousFocusByDrawer.delete(drawer);

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus({ preventScroll: true });
        }

        syncBodyScroll();
    };

    openDrawers().forEach((drawer) => {
        focusDrawer(drawer);
    });
    syncBodyScroll();

    document.addEventListener('click', (event) => {
        const closeButton = event.target.closest('[data-admin-drawer-close]');

        if (! closeButton) {
            return;
        }

        const drawer = closeButton.closest('[data-admin-drawer]');

        if (! drawer) {
            return;
        }

        closeDrawer(drawer);
    });

    document.addEventListener('keydown', (event) => {
        const drawer = activeDrawer();

        if (! drawer) {
            return;
        }

        if (event.key !== 'Escape') {
            if (event.key !== 'Tab') {
                return;
            }

            const elements = focusableElements(drawer);

            if (elements.length === 0) {
                event.preventDefault();
                focusDrawer(drawer);
                return;
            }

            const first = elements[0];
            const last = elements[elements.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }

            return;
        }

        closeDrawer(drawer);
    });

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type !== 'attributes' || mutation.attributeName !== 'data-admin-drawer-open') {
                return;
            }

            const drawer = mutation.target;

            if (drawer.dataset.adminDrawerOpen === 'true') {
                drawer.classList.remove('hidden');
                focusDrawer(drawer);
            }
        });

        syncBodyScroll();
    });

    document.querySelectorAll('[data-admin-drawer]').forEach((drawer) => {
        observer.observe(drawer, { attributes: true });
    });
}
