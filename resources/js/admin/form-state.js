const FORM_SELECTOR = '[data-admin-form-state]';
const SUBMITTING_ATTRIBUTE = 'data-admin-form-submitting';

export function bootAdminFormStates() {
    if (window.__catalogHubAdminFormStatesBooted) {
        return;
    }

    window.__catalogHubAdminFormStatesBooted = true;

    const markDirty = (form) => {
        if (form.dataset.adminFormSubmitting !== 'true') {
            form.dataset.adminFormDirty = 'true';
        }
    };

    document.addEventListener('input', (event) => {
        const form = event.target.closest(FORM_SELECTOR);
        if (form) markDirty(form);
    });

    document.addEventListener('change', (event) => {
        const form = event.target.closest(FORM_SELECTOR);
        if (form) markDirty(form);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target.closest(FORM_SELECTOR);

        if (! form) return;

        if (form.getAttribute(SUBMITTING_ATTRIBUTE) === 'true') {
            event.preventDefault();
            return;
        }

        form.setAttribute(SUBMITTING_ATTRIBUTE, 'true');
        form.querySelectorAll('[type="submit"]').forEach((button) => {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        });
    });

    document.addEventListener('admin:form-saved', (event) => {
        const form = event.target.closest?.(FORM_SELECTOR);
        if (! form) return;
        form.dataset.adminFormDirty = 'false';
        form.dataset.adminFormSubmitting = 'false';
    });

    document.addEventListener('admin:form-invalid', (event) => {
        const form = event.target.closest?.(FORM_SELECTOR);
        if (! form) return;
        form.dataset.adminFormSubmitting = 'false';
        form.querySelectorAll('[type="submit"]').forEach((button) => {
            button.disabled = false;
            button.removeAttribute('aria-disabled');
        });
    });

    window.addEventListener('beforeunload', (event) => {
        const dirty = document.querySelector(`${FORM_SELECTOR}[data-admin-form-dirty="true"][data-admin-form-leave-warning="true"]`);
        if (! dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
}
