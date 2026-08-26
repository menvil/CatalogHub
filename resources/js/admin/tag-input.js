function tagIdentity(value) {
    return value
        .replace(/[\p{Z}\u0009-\u000d\u0085]+/gu, ' ')
        .trim()
        .normalize('NFC')
        .toLocaleLowerCase();
}

export function bootTagInputs() {
    document.querySelectorAll('[data-ui-tag-input]').forEach((root) => {
        if (root.dataset.uiTagInputBooted === 'true') return;
        root.dataset.uiTagInputBooted = 'true';

        const input = root.querySelector('[data-ui-tag-input-text]');
        const addButton = root.querySelector('[data-ui-tag-input-add]');
        const chips = root.querySelector('[data-ui-tag-input-chips]');
        const error = root.querySelector('[data-ui-tag-input-error]');
        const max = Number.parseInt(root.dataset.uiTagInputMax ?? '20', 10);
        const disabled = root.dataset.uiTagInputDisabled === 'true';

        if (! (input instanceof HTMLInputElement) || ! (chips instanceof HTMLElement) || disabled) return;

        const currentChips = () => Array.from(chips.querySelectorAll('[data-ui-tag-input-chip]'));
        const showError = (message) => {
            if (! (error instanceof HTMLElement)) return;
            error.textContent = message;
            error.classList.toggle('hidden', message === '');
            input.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
        };
        const createChip = (name) => {
            const chip = document.createElement('span');
            chip.className = 'inline-flex max-w-full items-center gap-1 rounded-admin-badge bg-admin-surface-muted px-2.5 py-1 text-sm font-medium text-admin-text ring-1 ring-inset ring-admin-border';
            chip.dataset.uiTagInputChip = '';
            chip.dataset.tagName = name;

            const label = document.createElement('span');
            label.className = 'truncate';
            label.textContent = name;

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = root.dataset.uiTagInputName ?? 'tags[]';
            hidden.value = name;
            if (root.dataset.uiTagInputForm) hidden.setAttribute('form', root.dataset.uiTagInputForm);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'rounded px-1 text-admin-muted hover:text-admin-danger focus-visible:outline focus-visible:outline-2 focus-visible:outline-admin-primary';
            remove.dataset.uiTagInputRemove = '';
            remove.setAttribute('aria-label', `Remove ${name}`);
            remove.textContent = '×';

            chip.append(label, hidden, remove);
            chips.append(chip);
        };
        const add = () => {
            const name = input.value.replace(/[\p{Z}\u0009-\u000d\u0085]+/gu, ' ').trim().normalize('NFC');
            if (name === '') {
                showError('Enter a nonblank tag.');
                return;
            }
            if (currentChips().length >= max) {
                showError(`Maximum ${max} tags.`);
                return;
            }

            const identity = tagIdentity(name);
            const duplicate = currentChips().some((chip) => tagIdentity(chip.dataset.tagName ?? '') === identity);
            if (duplicate) {
                showError('That tag is already added.');
                input.select();
                return;
            }

            createChip(name);
            input.value = '';
            showError('');
            input.focus();
            input.dispatchEvent(new Event('input', { bubbles: true }));
        };

        addButton?.addEventListener('click', add);
        input.addEventListener('keydown', (event) => {
            if (event.isComposing || event.key !== 'Enter') return;
            event.preventDefault();
            add();
        });
        input.addEventListener('input', () => showError(''));
        chips.addEventListener('click', (event) => {
            if (! (event.target instanceof Element)) return;
            const remove = event.target.closest('[data-ui-tag-input-remove]');
            if (! remove) return;
            remove.closest('[data-ui-tag-input-chip]')?.remove();
            showError('');
            input.dispatchEvent(new Event('input', { bubbles: true }));
        });
    });
}
