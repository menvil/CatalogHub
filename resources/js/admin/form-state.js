const FORM_SELECTOR = '[data-admin-form-state]';
const SUBMITTING_ATTRIBUTE = 'data-admin-form-submitting';
const SUBMIT_CONTROL_SELECTOR = 'button:not([type]), button[type="submit"], input[type="submit"]';
const DISABLED_BY_STATE_ATTRIBUTE = 'data-admin-form-disabled-by-state';

export function bootAdminFormStates() {
    if (window.__catalogHubAdminFormStatesBooted) {
        return;
    }

    window.__catalogHubAdminFormStatesBooted = true;

    const resetSubmitControls = (form) => {
        form.querySelectorAll(`[${DISABLED_BY_STATE_ATTRIBUTE}="true"]`).forEach((button) => {
            button.disabled = false;
            button.removeAttribute('aria-disabled');
            button.removeAttribute(DISABLED_BY_STATE_ATTRIBUTE);
        });
    };

    const markDirty = (form) => {
        form.dataset.adminFormDirty = 'true';

        if (form.dataset.adminFormSubmitting === 'true') {
            form.dataset.adminFormChangedWhileSubmitting = 'true';
        }
    };

    document.addEventListener('input', (event) => {
        if (! (event.target instanceof Element)) return;

        const form = event.target.closest(FORM_SELECTOR);
        if (form) markDirty(form);
    });

    document.addEventListener('change', (event) => {
        if (! (event.target instanceof Element)) return;

        const form = event.target.closest(FORM_SELECTOR);
        if (form) markDirty(form);
    });

    document.addEventListener('submit', (event) => {
        if (! (event.target instanceof Element)) return;

        const form = event.target.closest(FORM_SELECTOR);

        if (! form) return;

        if (form.getAttribute(SUBMITTING_ATTRIBUTE) === 'true') {
            event.preventDefault();
            return;
        }

        form.setAttribute(SUBMITTING_ATTRIBUTE, 'true');
        form.dataset.adminFormChangedWhileSubmitting = 'false';
        form.querySelectorAll(SUBMIT_CONTROL_SELECTOR).forEach((button) => {
            if (button.disabled) return;

            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
            button.setAttribute(DISABLED_BY_STATE_ATTRIBUTE, 'true');
        });
    });

    document.addEventListener('admin:form-saved', (event) => {
        const form = event.target.closest?.(FORM_SELECTOR);
        if (! form) return;
        form.dataset.adminFormDirty = form.dataset.adminFormChangedWhileSubmitting === 'true' ? 'true' : 'false';
        form.dataset.adminFormSubmitting = 'false';
        form.dataset.adminFormChangedWhileSubmitting = 'false';
        resetSubmitControls(form);
    });

    document.addEventListener('admin:form-invalid', (event) => {
        const form = event.target.closest?.(FORM_SELECTOR);
        if (! form) return;
        form.dataset.adminFormSubmitting = 'false';
        resetSubmitControls(form);
    });

    window.addEventListener('beforeunload', (event) => {
        const dirty = document.querySelector(`${FORM_SELECTOR}[data-admin-form-dirty="true"][data-admin-form-leave-warning="true"]`);
        if (! dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });
}
