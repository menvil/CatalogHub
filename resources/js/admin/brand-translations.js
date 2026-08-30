export function bootBrandTranslations() {
    if (window.__catalogHubBrandTranslationsBooted) {
        return;
    }

    window.__catalogHubBrandTranslationsBooted = true;

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }

        const trigger = event.target.closest('[data-brand-translation-copy-source]');

        if (! trigger) {
            return;
        }

        const targetId = trigger.dataset.brandTranslationCopyTarget;
        const sourceValue = trigger.dataset.brandTranslationSourceValue;
        const target = targetId ? document.getElementById(targetId) : null;

        if (! (target instanceof HTMLInputElement) || typeof sourceValue !== 'string') {
            return;
        }

        if (target.value.trim() !== '' && target.value !== sourceValue
            && ! window.confirm('Replace the current localized name with the canonical Brand name?')) {
            return;
        }

        target.value = sourceValue;
        target.dispatchEvent(new Event('input', { bubbles: true }));
        target.dispatchEvent(new Event('change', { bubbles: true }));
        target.focus({ preventScroll: true });
    });
}
