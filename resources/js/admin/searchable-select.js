function elements(root) {
    return {
        input: root.querySelector('[data-ui-searchable-select-input]'),
        native: root.querySelector('[data-ui-searchable-select-native]'),
        listbox: root.querySelector('[data-ui-searchable-select-listbox]'),
        options: Array.from(root.querySelectorAll('[data-ui-searchable-select-option]')),
        empty: root.querySelector('[data-ui-searchable-select-empty]'),
    }
}

function visibleOptions(root) {
    return elements(root).options.filter((option) => ! option.hidden)
}

function setActive(root, option) {
    const { input, options } = elements(root)
    options.forEach((candidate) => candidate.classList.toggle('ring-2', candidate === option))
    if (input) {
        option ? input.setAttribute('aria-activedescendant', option.id) : input.removeAttribute('aria-activedescendant')
    }
    option?.scrollIntoView({ block: 'nearest' })
}

function open(root) {
    const { input, listbox } = elements(root)
    if (! input || ! listbox || input.disabled) return

    document.querySelectorAll('[data-ui-searchable-select]').forEach((candidate) => {
        if (candidate !== root) close(candidate)
    })
    listbox.hidden = false
    input.setAttribute('aria-expanded', 'true')
    root.querySelector('[data-ui-searchable-select-chevron]')?.classList.add('rotate-180')
}

function close(root, restoreLabel = true) {
    const { input, listbox } = elements(root)
    if (! input || ! listbox) return
    listbox.hidden = true
    input.setAttribute('aria-expanded', 'false')
    root.querySelector('[data-ui-searchable-select-chevron]')?.classList.remove('rotate-180')
    setActive(root, null)
    if (restoreLabel) {
        filter(root, '')
        input.value = root.dataset.selectedLabel ?? ''
    }
}

function filter(root, query) {
    const { options, empty } = elements(root)
    const needle = query.trim().toLocaleLowerCase()
    let visible = 0

    options.forEach((option) => {
        const haystack = `${option.dataset.label ?? ''} ${option.dataset.search ?? ''}`.toLocaleLowerCase()
        option.hidden = needle !== '' && ! haystack.includes(needle)
        if (! option.hidden) visible++
    })
    if (empty) empty.hidden = visible !== 0
    setActive(root, null)
}

function choose(root, option) {
    const { input, native, options } = elements(root)
    if (! input || ! native) return
    const value = option?.dataset.value ?? ''
    const label = option?.dataset.label ?? ''
    native.value = value
    root.dataset.selectedLabel = label
    input.value = label
    options.forEach((candidate) => candidate.setAttribute('aria-selected', candidate === option ? 'true' : 'false'))
    native.dispatchEvent(new Event('input', { bubbles: true }))
    native.dispatchEvent(new Event('change', { bubbles: true }))
    filter(root, '')
    close(root)
}

export function bootSearchableSelects() {
    if (window.__catalogHubSearchableSelectsBooted) return
    window.__catalogHubSearchableSelectsBooted = true

    document.addEventListener('focusin', (event) => {
        if (! (event.target instanceof Element)) return
        const input = event.target.closest('[data-ui-searchable-select-input]')
        const root = input?.closest('[data-ui-searchable-select]')
        if (root) open(root)
    })

    document.addEventListener('input', (event) => {
        if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-ui-searchable-select-input]')) return
        const root = event.target.closest('[data-ui-searchable-select]')
        if (! root) return
        open(root)
        filter(root, event.target.value)
    })

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) return
        const option = event.target.closest('[data-ui-searchable-select-option]')
        if (option) {
            const root = option.closest('[data-ui-searchable-select]')
            if (root) choose(root, option)
            return
        }
        const clear = event.target.closest('[data-ui-searchable-select-clear]')
        if (clear) {
            const root = clear.closest('[data-ui-searchable-select]')
            if (root) choose(root, null)
            return
        }
        document.querySelectorAll('[data-ui-searchable-select]').forEach((root) => {
            if (! root.contains(event.target)) close(root)
        })
    })

    document.addEventListener('keydown', (event) => {
        if (! (event.target instanceof HTMLInputElement) || ! event.target.matches('[data-ui-searchable-select-input]')) return
        const root = event.target.closest('[data-ui-searchable-select]')
        if (! root) return
        const options = visibleOptions(root)
        const activeId = event.target.getAttribute('aria-activedescendant')
        const current = options.findIndex((option) => option.id === activeId)

        if (event.key === 'Escape') {
            event.preventDefault()
            close(root)
            event.target.blur()
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault()
            open(root)
            const offset = event.key === 'ArrowDown' ? 1 : -1
            const index = current < 0 ? (offset > 0 ? 0 : options.length - 1) : (current + offset + options.length) % options.length
            setActive(root, options[index] ?? null)
        } else if (event.key === 'Enter' && current >= 0) {
            event.preventDefault()
            choose(root, options[current])
        }
    })
}
