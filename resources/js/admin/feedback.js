export function bootAdminFeedback() {
    if (window.__catalogHubAdminFeedbackBooted) {
        return;
    }

    window.__catalogHubAdminFeedbackBooted = true;

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const dismiss = event.target.closest('[data-ui-feedback-dismiss]');

        if (dismiss) {
            dismiss.closest('[data-ui-toast], [data-ui-alert]')?.remove();
        }
    });
}
