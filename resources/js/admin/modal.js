export function bootAdminModals() {
    if (window.__catalogHubAdminModalsBooted) {
        return;
    }

    window.__catalogHubAdminModalsBooted = true;

    const previousFocusByModal = new WeakMap();
    const modalOpeningOrder = [];
    const focusableSelector = [
        'a[href]',
        'button:not([disabled])',
        'input:not([disabled])',
        'select:not([disabled])',
        'textarea:not([disabled])',
        '[tabindex]:not([tabindex="-1"])',
    ].join(',');
    const openModals = () => modalOpeningOrder.filter((modal) => modal.dataset.adminModalOpen === 'true');
    const dialogFor = (modal) => modal.querySelector('[role="dialog"]') ?? modal;
    const focusableElements = (modal) => Array.from(dialogFor(modal).querySelectorAll(focusableSelector))
        .filter((element) => element.getClientRects().length > 0);

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
        const existingIndex = modalOpeningOrder.indexOf(modal);
        if (existingIndex !== -1) modalOpeningOrder.splice(existingIndex, 1);
        modalOpeningOrder.push(modal);
        const focusTarget = focusableElements(modal)[0] ?? dialogFor(modal);

        if (! focusTarget.hasAttribute('tabindex')) {
            focusTarget.setAttribute('tabindex', '-1');
        }

        focusTarget.focus({ preventScroll: true });
        syncBody();
    };

    const closeModal = (modal) => {
        modal.dataset.adminModalOpen = 'false';
        modal.classList.add('hidden');
        const orderIndex = modalOpeningOrder.indexOf(modal);
        if (orderIndex !== -1) modalOpeningOrder.splice(orderIndex, 1);

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
        const currentlyOpen = openModals();
        const modal = [...currentlyOpen].reverse().find((candidate) => candidate.contains(document.activeElement))
            ?? currentlyOpen.at(-1);

        if (! modal) {
            return;
        }

        if (event.key === 'Escape') {
            closeModal(modal);
            return;
        }

        if (event.key === 'Tab') {
            const elements = focusableElements(modal);
            const activeInsideModal = dialogFor(modal).contains(document.activeElement);

            if (elements.length === 0) {
                event.preventDefault();
                dialogFor(modal).focus({ preventScroll: true });
                return;
            }

            const first = elements[0];
            const last = elements[elements.length - 1];

            if (! activeInsideModal) {
                event.preventDefault();
                (event.shiftKey ? last : first).focus({ preventScroll: true });
            } else if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (! event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }
    });

    document.querySelectorAll('[data-admin-modal][data-admin-modal-open="true"]').forEach((modal) => openModal(modal));
    syncBody();
}
