export function bootCategoryDetail() {
    if (window.__catalogHubCategoryDetailBooted) return;
    window.__catalogHubCategoryDetailBooted = true;

    document.addEventListener('click', async (event) => {
        if (!(event.target instanceof Element)) return;
        const button = event.target.closest('[data-category-copy]');
        if (!(button instanceof HTMLButtonElement)) return;
        const feedback = document.querySelector('[data-category-copy-feedback]');
        if (!feedback) return;
        try {
            await navigator.clipboard.writeText(button.dataset.categoryCopy);
            feedback.classList.remove('sr-only');
            feedback.textContent = `${button.getAttribute('aria-label')} copied.`;
        } catch {
            feedback.classList.remove('sr-only');
            feedback.textContent = 'Could not copy. Select the displayed value and copy it manually.';
        }
    });
}
