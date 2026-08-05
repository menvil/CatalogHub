export function bootAdminModals() {
    if (window.__catalogHubAdminModalsBooted) {
        return;
    }

    window.__catalogHubAdminModalsBooted = true;

    const previousFocusByModal = new WeakMap();
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');
    const openModals = () => Array.from(document.querySelectorAll('[data-admin-modal][data-admin-modal-open="true"]'));
    const focusableElements = (modal) => Array.from(modal.querySelectorAll(focusableSelector));

    const syncBody = () => {
        document.body.classList.toggle(
            'admin-modal-open',
            openModals().some((modal) => modal.dataset.adminModalContained !== 'true'),
        );
    };

    const openModal = (modal, trigger = document.activeElement) => {
        previousFocusByModal.set(modal, trigger);
        modal.dataset.adminModalOpen = 'true';
        modal.classList.remove('hidden');
        focusableElements(modal)[0]?.focus({ preventScroll: true });
        syncBody();
    };

    const closeModal = (modal) => {
        modal.dataset.adminModalOpen = 'false';
        modal.classList.add('hidden');

        const previousFocus = previousFocusByModal.get(modal);
        previousFocusByModal.delete(modal);
        previousFocus?.focus?.({ preventScroll: true });
        syncBody();
    };

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const openButton = event.target.closest('[data-admin-modal-open-target]');
        const closeButton = event.target.closest('[data-admin-modal-close]');
        const confirmButton = event.target.closest('[data-admin-modal-confirm]');

        if (openButton) {
            const modal = document.getElementById(openButton.dataset.adminModalOpenTarget);

            if (modal) {
                openModal(modal, openButton);
            }
        }

        if (closeButton) {
            const modal = closeButton.closest('[data-admin-modal]');

            if (modal) {
                closeModal(modal);
            }
        }

        if (confirmButton && confirmButton.getAttribute('aria-busy') === 'true') {
            event.preventDefault();
            return;
        }

        if (confirmButton) {
            confirmButton.setAttribute('aria-busy', 'true');
            confirmButton.disabled = true;
        }
    });

    document.addEventListener('keydown', (event) => {
        const modal = openModals().at(-1);

        if (! modal) {
            return;
        }

        if (event.key === 'Escape') {
            closeModal(modal);
            return;
        }

        if (event.key === 'Tab') {
            const elements = focusableElements(modal);

            if (elements.length === 0) {
                event.preventDefault();
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
        }
    });

    openModals().forEach((modal) => openModal(modal));
    syncBody();
}
