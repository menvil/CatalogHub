const START_SELECTOR = '[data-action-progress-start], [data-action-progress-retry]';

export function bootActionProgress(root = document) {
    root.addEventListener('click', (event) => {
        const trigger = event.target.closest(START_SELECTOR);

        if (!trigger) return;

        const progress = trigger.closest('[data-ui-action-progress]');

        if (!progress || progress.dataset.actionProgressStarted === 'true') {
            event.preventDefault();
            return;
        }

        progress.dataset.actionProgressStarted = 'true';
        trigger.disabled = true;
        trigger.setAttribute('aria-disabled', 'true');

        progress.dispatchEvent(new CustomEvent(
            trigger.matches('[data-action-progress-retry]') ? 'admin:action-progress-retry' : 'admin:action-progress-start',
            { bubbles: true },
        ));
    });
}
