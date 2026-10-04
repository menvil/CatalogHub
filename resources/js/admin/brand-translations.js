function copyableFields() {
    return [...document.querySelectorAll('[data-brand-translation-copy-source]')]
        .map((trigger) => ({
            target: document.getElementById(trigger.dataset.brandTranslationCopyTarget),
            sourceValue: trigger.dataset.brandTranslationSourceValue,
        }))
        .filter(({ target, sourceValue }) => (target instanceof HTMLInputElement || target instanceof HTMLTextAreaElement)
            && typeof sourceValue === 'string' && sourceValue.trim() !== '');
}

function updateCounter(target) {
    const counter = document.querySelector(`[data-brand-translation-counter="${target.id}"]`);
    if (counter) {
        counter.textContent = `${target.value.length} / ${target.maxLength}`;
    }
}

export function bootBrandTranslations() {
    if (window.__catalogHubBrandTranslationsBooted) {
        return;
    }
    window.__catalogHubBrandTranslationsBooted = true;

    const form = document.getElementById('brand-translation-form');
    const controls = form ? [...form.querySelectorAll('input[name]:not([type=hidden]), textarea[name], select[name]')] : [];
    const initialValues = controls.map((control) => control.value);
    const languages = [...document.querySelectorAll('[data-brand-translation-language-selector]')];
    const initialLanguages = new Map(languages.map((control) => [control, control.value]));

    document.addEventListener('change', (event) => {
        if (event.target instanceof HTMLSelectElement && event.target.matches('[data-brand-translation-language-selector]')) {
            const url = event.target.selectedOptions[0]?.dataset.languageUrl;
            if (url) {
                const dirty = controls.some((control, index) => control.value !== initialValues[index]);
                if (dirty && ! window.confirm('Discard unsaved translation changes?')) {
                    event.target.value = initialLanguages.get(event.target);
                    return;
                }
                window.location.assign(new URL(url, window.location.href).href);
            }
        }
    });

    document.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement || event.target instanceof HTMLTextAreaElement) {
            updateCounter(event.target);
        }
    });

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) {
            return;
        }
        const trigger = event.target.closest('[data-brand-translation-copy-source], [data-brand-translation-copy-all]');
        if (! trigger) {
            return;
        }
        const copyAll = trigger.hasAttribute('data-brand-translation-copy-all');
        const fields = copyableFields().filter(({ target }) => copyAll || target.id === trigger.dataset.brandTranslationCopyTarget);
        if (fields.length === 0) {
            return;
        }
        if (fields.some(({ target, sourceValue }) => target.value.trim() !== '' && target.value !== sourceValue)
            && ! window.confirm('Replace current target values with source values?')) {
            return;
        }
        for (const { target, sourceValue } of fields) {
            target.value = sourceValue;
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
        fields[0].target.focus({ preventScroll: true });
    });
}
