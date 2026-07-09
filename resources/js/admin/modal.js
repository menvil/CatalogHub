export function bootAdminModals() {
    document.addEventListener('click', (event) => {
        const closeButton = event.target.closest('[data-admin-modal-close]');

        if (! closeButton) {
            return;
        }

        const modal = closeButton.closest('[data-admin-modal]');

        if (! modal) {
            return;
        }

        modal.dataset.adminModalOpen = 'false';
        modal.classList.add('hidden');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('[data-admin-modal][data-admin-modal-open="true"]').forEach((modal) => {
            modal.dataset.adminModalOpen = 'false';
            modal.classList.add('hidden');
        });
    });
}
