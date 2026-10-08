export function bootCategoryList() {
    if (window.__catalogHubCategoryListBooted) return;
    window.__catalogHubCategoryListBooted = true;

    let searchTimer;

    const submitSearch = (input, immediate = false) => {
        window.clearTimeout(searchTimer);

        if (immediate) {
            input.form?.requestSubmit();
            return;
        }

        searchTimer = window.setTimeout(() => input.form?.requestSubmit(), 300);
    };

    document.addEventListener('change', (event) => {
        if (! (event.target instanceof HTMLSelectElement)) return;
        if (! event.target.closest('.category-list-filters, .category-list-per-page, .category-list-mobile-sort')) return;

        event.target.form?.requestSubmit();
    });

    document.addEventListener('input', (event) => {
        if (! (event.target instanceof HTMLInputElement)) return;
        if (! event.target.matches('[data-category-list-search]') || event.isComposing) return;

        submitSearch(event.target, event.target.value === '');
    });

    document.addEventListener('search', (event) => {
        if (! (event.target instanceof HTMLInputElement)) return;
        if (! event.target.matches('[data-category-list-search]')) return;

        submitSearch(event.target, true);
    });
}
