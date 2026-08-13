function closeSelect(select, restoreFocus = false) {
    const trigger = select.querySelector('[data-ui-select-trigger]');
    const menu = select.querySelector('[data-ui-select-menu]');

    if (! trigger || ! menu) return;
    menu.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    select.querySelector('[data-ui-select-chevron]')?.classList.remove('rotate-180');
    if (restoreFocus) trigger.focus();
}

function openSelect(select) {
    document.querySelectorAll('[data-ui-select]').forEach((candidate) => {
        if (candidate !== select) closeSelect(candidate);
    });

    const trigger = select.querySelector('[data-ui-select-trigger]');
    const menu = select.querySelector('[data-ui-select-menu]');
    if (! trigger || ! menu || trigger.disabled) return;

    menu.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    select.querySelector('[data-ui-select-chevron]')?.classList.add('rotate-180');
    (menu.querySelector('[role="option"][aria-selected="true"]') ?? menu.querySelector('[role="option"]'))?.focus();
}

function selectOption(option) {
    const select = option.closest('[data-ui-select]');
    const nativeSelect = select?.querySelector('[data-ui-select-native]');
    const value = select?.querySelector('[data-ui-select-value]');
    if (! select || ! nativeSelect || ! value) return;

    nativeSelect.value = option.dataset.value ?? '';
    value.textContent = option.textContent.trim();
    select.querySelectorAll('[role="option"]').forEach((candidate) => {
        candidate.setAttribute('aria-selected', candidate === option ? 'true' : 'false');
    });
    nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
    nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
    closeSelect(select, true);
}

function syncRequiredCheckboxGroup(input) {
    const group = input.closest('[data-ui-scrollable-checkbox-list]');
    if (! group) return;

    const inputs = Array.from(group.querySelectorAll('[data-ui-checkbox-group-required]'));
    const hasSelection = inputs.some((candidate) => candidate.checked);
    inputs.forEach((candidate) => {
        candidate.required = ! hasSelection;
    });
}

export function bootAdminSelects() {
    if (window.__catalogHubAdminSelectsBooted) return;
    window.__catalogHubAdminSelectsBooted = true;

    document.querySelectorAll('[data-ui-checkbox-group-required]').forEach(syncRequiredCheckboxGroup);

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) return;

        document.querySelectorAll('[data-ui-checkbox-dropdown] details[open]').forEach((details) => {
            if (! details.contains(event.target)) details.removeAttribute('open');
        });

        const trigger = event.target.closest('[data-ui-select-trigger]');
        if (trigger) {
            const select = trigger.closest('[data-ui-select]');
            if (! select) return;
            trigger.getAttribute('aria-expanded') === 'true' ? closeSelect(select) : openSelect(select);
            return;
        }

        const option = event.target.closest('[data-ui-select-option]');
        if (option) {
            selectOption(option);
            return;
        }

        document.querySelectorAll('[data-ui-select]').forEach((select) => closeSelect(select));
    });

    document.addEventListener('keydown', (event) => {
        if (! (event.target instanceof Element)) return;
        const select = event.target.closest('[data-ui-select]');
        if (! select) return;

        const options = Array.from(select.querySelectorAll('[data-ui-select-option]'));
        const current = options.indexOf(event.target.closest('[data-ui-select-option]'));

        if (event.key === 'Escape') {
            event.preventDefault();
            closeSelect(select, true);
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (select.querySelector('[data-ui-select-menu]')?.hidden) {
                openSelect(select);
                return;
            }
            const offset = event.key === 'ArrowDown' ? 1 : -1;
            options[(current + offset + options.length) % options.length]?.focus();
        } else if ((event.key === 'Enter' || event.key === ' ') && current >= 0) {
            event.preventDefault();
            selectOption(options[current]);
        }
    });

    document.addEventListener('change', (event) => {
        if (! (event.target instanceof HTMLInputElement) || event.target.type !== 'checkbox') return;
        if (event.target.matches('[data-ui-checkbox-group-required]')) syncRequiredCheckboxGroup(event.target);
        const dropdown = event.target.closest('[data-ui-checkbox-dropdown]');
        const count = dropdown?.querySelector('[data-ui-checkbox-dropdown-count]');
        if (! dropdown || ! count) return;
        count.textContent = String(dropdown.querySelectorAll('input[type="checkbox"]:checked').length);
    });
}
