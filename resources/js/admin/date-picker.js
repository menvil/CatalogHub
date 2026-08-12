export function bootAdminDatePickers() {
    if (window.__catalogHubAdminDatePickersBooted) return;
    window.__catalogHubAdminDatePickersBooted = true;

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) return;
        const trigger = event.target.closest('[data-ui-date-picker-trigger]');
        const picker = trigger?.closest('[data-ui-date-picker]');
        const input = picker?.querySelector('input[type="date"]');
        if (! input || input.disabled) return;

        if (typeof input.showPicker === 'function') {
            input.showPicker();
        } else {
            input.focus();
        }
    });
}
