function elements(root) {
    return {
        input: root.querySelector('[data-ui-searchable-select-input]'),
        native: root.querySelector('[data-ui-searchable-select-native]'),
        listbox: root.querySelector('[data-ui-searchable-select-listbox]'),
        options: Array.from(root.querySelectorAll('[data-ui-searchable-select-option]')),
        empty: root.querySelector('[data-ui-searchable-select-empty]'),
        loading: root.querySelector('[data-ui-searchable-select-loading]'),
    }
}

const remoteState = new WeakMap()

function remoteConfiguration(root) {
    const url = root.dataset.searchUrl
    return url ? { url } : null
}

function replaceRemoteOptions(root, remoteOptions) {
    const { listbox, native, empty, loading } = elements(root)
    if (! listbox || ! native) return

    const selectedValue = native.value
    const selectedLabel = root.dataset.selectedLabel ?? ''
    listbox.querySelectorAll('[data-ui-searchable-select-option]').forEach((option) => option.remove())
    native.querySelectorAll('option:not(:first-child)').forEach((option) => option.remove())

    const options = Array.isArray(remoteOptions) ? remoteOptions : []
    if (selectedValue && ! options.some((option) => String(option.value) === selectedValue)) {
        options.unshift({ value: selectedValue, label: selectedLabel, search: selectedLabel })
    }

    options.forEach((remoteOption, index) => {
        const value = String(remoteOption.value ?? '')
        const label = String(remoteOption.label ?? '')
        const search = String(remoteOption.search ?? label)
        if (! value || ! label) return

        const nativeOption = document.createElement('option')
        nativeOption.value = value
        nativeOption.textContent = label
        nativeOption.selected = value === selectedValue
        native.append(nativeOption)

        const option = document.createElement('div')
        option.id = `${native.id}-option-${index}`
        option.className = 'flex min-h-9 cursor-pointer items-center rounded-admin-input px-3 py-2 text-sm text-admin-text hover:bg-admin-surface-muted aria-selected:bg-admin-primary aria-selected:text-white'
        option.setAttribute('role', 'option')
        option.setAttribute('aria-selected', value === selectedValue ? 'true' : 'false')
        option.dataset.uiSearchableSelectOption = ''
        option.dataset.value = value
        option.dataset.label = label
        option.dataset.search = search
        option.textContent = label
        listbox.insertBefore(option, empty ?? loading ?? null)
    })

    if (loading) loading.hidden = true
    if (empty) empty.hidden = options.length !== 0
}

async function searchRemote(root, query) {
    const configuration = remoteConfiguration(root)
    if (! configuration) return

    const previous = remoteState.get(root)
    previous?.controller?.abort()
    const controller = new AbortController()
    remoteState.set(root, { ...previous, controller })
    const { loading, empty, input } = elements(root)
    if (loading) loading.hidden = false
    if (empty) empty.hidden = true
    input?.setAttribute('aria-busy', 'true')

    try {
        const url = new URL(configuration.url, window.location.origin)
        if (query.trim()) url.searchParams.set('q', query.trim())
        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal: controller.signal,
        })
        if (! response.ok) throw new Error(`Organization search failed with ${response.status}.`)
        const payload = await response.json()
        if (remoteState.get(root)?.controller !== controller) return
        replaceRemoteOptions(root, payload.options)
    } catch (error) {
        if (error?.name !== 'AbortError') {
            replaceRemoteOptions(root, [])
        }
    } finally {
        if (remoteState.get(root)?.controller === controller) {
            input?.removeAttribute('aria-busy')
        }
    }
}

function scheduleRemoteSearch(root, query) {
    const previous = remoteState.get(root)
    if (previous?.timer) window.clearTimeout(previous.timer)
    const timer = window.setTimeout(() => void searchRemote(root, query), 180)
    remoteState.set(root, { ...previous, timer })
}

function cancelRemoteSearch(root) {
    const previous = remoteState.get(root)
    if (previous?.timer) window.clearTimeout(previous.timer)
    previous?.controller?.abort()
    remoteState.set(root, { ...previous, timer: null, controller: null })

    const { input, loading } = elements(root)
    input?.removeAttribute('aria-busy')
    if (loading) loading.hidden = true
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
    if (remoteConfiguration(root) && ! remoteState.get(root)?.loaded) {
        remoteState.set(root, { ...remoteState.get(root), loaded: true })
        void searchRemote(root, '')
    }
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
    cancelRemoteSearch(root)
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
        if (remoteConfiguration(root)) {
            scheduleRemoteSearch(root, event.target.value)
        } else {
            filter(root, event.target.value)
        }
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
