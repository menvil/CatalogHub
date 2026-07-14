export function bootFacetForms(root = document) {
    root.querySelectorAll('[data-facet-form]').forEach((form) => {
        if (!(form instanceof HTMLFormElement) || form.dataset.facetFormBound === 'true') {
            return;
        }

        form.dataset.facetFormBound = 'true';
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const grouped = new Map();

            for (const [rawKey, rawValue] of new FormData(form).entries()) {
                const key = rawKey.endsWith('[]') ? rawKey.slice(0, -2) : rawKey;
                const value = String(rawValue).trim();

                if (key === 'page' || value === '') {
                    continue;
                }

                grouped.set(key, [...(grouped.get(key) ?? []), value]);
            }

            const params = new URLSearchParams();
            [...grouped.keys()].sort().forEach((key) => {
                const values = [...new Set(grouped.get(key))].sort();
                params.set(key, values.join(','));
            });

            const query = params.toString().replaceAll('%2C', ',');
            window.location.assign(`${form.action}${query === '' ? '' : `?${query}`}`);
        });
    });
}
