// CA-018 Category Create/Edit progressive enhancement. The server stays authoritative:
// revisions, sibling order and the reviewed slug impact are all revalidated on Save.

const PREVIEW_DELAY = 350;

export function slugify(value) {
    return value
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .replace(/&/g, ' and ')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 255);
}

function scopeRevisions(form) {
    const script = form.querySelector('[data-category-scope-revisions]');
    try {
        return JSON.parse(script?.textContent || '{}');
    } catch {
        return {};
    }
}

function bindParent(form) {
    const parent = form.querySelector('select[name="parent_id"]');
    const destination = form.querySelector('[data-category-destination-revision]');
    if (!parent || !destination) return;
    const revisions = scopeRevisions(form);
    const position = form.querySelector('select[name="display_position"]');
    const note = form.querySelector('[data-category-order-parent-note]');
    const original = form.dataset.originalParent ?? null;
    const originalPosition = position?.value ?? null;

    parent.addEventListener('change', () => {
        // Token = the selected parent's scope revision as loaded with this page.
        destination.value = String(revisions[parent.value] ?? 0);
        if (!position || original === null) return;
        const moved = parent.value !== original;
        if (note) note.hidden = !moved;
        if (moved && position.value !== originalPosition) {
            position.value = originalPosition;
            position.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}

function bindSlugSuggestion(form) {
    const name = form.querySelector('[data-category-name]');
    const slug = form.querySelector('[data-category-slug]');
    if (!name || !slug) return;
    let touched = slug.value.trim() !== '';
    slug.addEventListener('input', () => {
        touched = slug.value.trim() !== '';
    });
    name.addEventListener('input', () => {
        if (!touched) slug.value = slugify(name.value);
    });
}

function bindSlugImpact(form) {
    const slug = form.querySelector('[data-category-slug]');
    const container = form.querySelector('[data-category-slug-impact-container]');
    const url = form.dataset.slugImpactUrl;
    const current = form.dataset.currentSlug ?? '';
    if (!slug || !container || !url) return;
    let timer = null;
    let sequence = 0;

    const render = async () => {
        const proposed = slug.value.trim();
        const request = ++sequence;
        if (proposed === current) {
            container.innerHTML = '';
            return;
        }
        if (proposed === '' || proposed.length > 255) return;
        container.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(`${url}?${new URLSearchParams({ slug: proposed })}`, {
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (request !== sequence) return;
            container.innerHTML = response.ok && !response.redirected
                ? await response.text()
                : '<p class="category-form-note" data-slug-impact-unavailable>The routing impact could not be loaded. It will be shown for review when you save.</p>';
        } catch {
            if (request === sequence) {
                container.innerHTML = '<p class="category-form-note" data-slug-impact-unavailable>The routing impact could not be loaded. It will be shown for review when you save.</p>';
            }
        } finally {
            if (request === sequence) container.removeAttribute('aria-busy');
        }
    };

    slug.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(render, PREVIEW_DELAY);
    });
}

export function bootCategoryForm() {
    if (window.__catalogHubCategoryFormBooted) return;
    window.__catalogHubCategoryFormBooted = true;

    document.querySelectorAll('form[data-category-form]').forEach((form) => {
        bindParent(form);
        if (form.dataset.categoryForm === 'create') bindSlugSuggestion(form);
        else bindSlugImpact(form);
    });
}
