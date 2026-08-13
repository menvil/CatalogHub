const monthFormatter = new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' })
const dateFormatter = new Intl.DateTimeFormat('en', { day: '2-digit', month: 'short', year: 'numeric' })

function parseDate(value) {
    const match = String(value ?? '').match(/^(\d{4})-(\d{2})-(\d{2})/)
    if (! match) return null

    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
    return Number.isNaN(date.getTime()) ? null : date
}

function dateValue(date) {
    const year = String(date.getFullYear())
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')
    return `${year}-${month}-${day}`
}

function closePicker(picker, restoreFocus = false) {
    const trigger = picker.querySelector('[data-ui-date-picker-trigger]')
    const panel = picker.querySelector('[data-ui-date-picker-panel]')
    if (! trigger || ! panel) return

    panel.hidden = true
    trigger.setAttribute('aria-expanded', 'false')
    if (restoreFocus) trigger.focus()
}

function syncValue(picker, selectedDate) {
    const value = picker.querySelector('[data-ui-date-picker-value]')
    const display = picker.querySelector('[data-ui-date-picker-display]')
    const time = picker.querySelector('[data-ui-date-picker-time]')
    if (! value || ! display || ! selectedDate) return

    const day = dateValue(selectedDate)
    const timeValue = time?.value || '00:00'
    value.value = picker.dataset.uiDatePickerMode === 'datetime' ? `${day}T${timeValue}` : day
    display.textContent = `${dateFormatter.format(selectedDate)}${picker.dataset.uiDatePickerMode === 'datetime' ? `, ${timeValue}` : ''}`
    value.dispatchEvent(new Event('input', { bubbles: true }))
    value.dispatchEvent(new Event('change', { bubbles: true }))
}

function renderPicker(picker) {
    const grid = picker.querySelector('[data-ui-date-picker-grid]')
    const monthLabel = picker.querySelector('[data-ui-date-picker-month]')
    const value = picker.querySelector('[data-ui-date-picker-value]')
    if (! grid || ! monthLabel || ! value) return

    const selected = parseDate(value.value)
    const initial = selected ?? new Date()
    const [viewYear, viewMonth] = (picker.dataset.viewMonth ?? `${initial.getFullYear()}-${initial.getMonth()}`).split('-').map(Number)
    const first = new Date(viewYear, viewMonth, 1)
    const days = new Date(viewYear, viewMonth + 1, 0).getDate()
    const leading = (first.getDay() + 6) % 7
    const minimum = parseDate(picker.dataset.min)
    const maximum = parseDate(picker.dataset.max)

    picker.dataset.viewMonth = `${viewYear}-${viewMonth}`
    monthLabel.textContent = monthFormatter.format(first)
    grid.replaceChildren()

    for (let index = 0; index < leading; index += 1) {
        const spacer = document.createElement('span')
        spacer.setAttribute('aria-hidden', 'true')
        grid.append(spacer)
    }

    for (let day = 1; day <= days; day += 1) {
        const candidate = new Date(viewYear, viewMonth, day)
        const button = document.createElement('button')
        const isSelected = selected && dateValue(candidate) === dateValue(selected)
        const isDisabled = (minimum && candidate < minimum) || (maximum && candidate > maximum)
        button.type = 'button'
        button.textContent = String(day)
        button.dataset.uiDatePickerDay = dateValue(candidate)
        button.disabled = Boolean(isDisabled)
        button.setAttribute('aria-pressed', isSelected ? 'true' : 'false')
        button.className = 'inline-flex aspect-square cursor-pointer items-center justify-center rounded-admin-input text-sm text-admin-text hover:bg-admin-primary-soft focus:outline-none focus:ring-2 focus:ring-admin-primary/20 disabled:cursor-not-allowed disabled:opacity-30 aria-pressed:bg-admin-primary aria-pressed:font-semibold aria-pressed:text-white'
        grid.append(button)
    }
}

function openPicker(picker) {
    document.querySelectorAll('[data-ui-date-picker]').forEach((candidate) => {
        if (candidate !== picker) closePicker(candidate)
    })

    const panel = picker.querySelector('[data-ui-date-picker-panel]')
    const trigger = picker.querySelector('[data-ui-date-picker-trigger]')
    if (! panel || ! trigger || trigger.disabled) return

    const selected = parseDate(picker.querySelector('[data-ui-date-picker-value]')?.value) ?? new Date()
    picker.dataset.viewMonth = `${selected.getFullYear()}-${selected.getMonth()}`
    renderPicker(picker)
    panel.hidden = false
    trigger.setAttribute('aria-expanded', 'true')
}

export function bootAdminDatePickers() {
    if (window.__catalogHubAdminDatePickersBooted) return
    window.__catalogHubAdminDatePickersBooted = true

    document.addEventListener('click', (event) => {
        if (! (event.target instanceof Element)) return

        const trigger = event.target.closest('[data-ui-date-picker-trigger]')
        if (trigger) {
            const picker = trigger.closest('[data-ui-date-picker]')
            if (! picker) return
            trigger.getAttribute('aria-expanded') === 'true' ? closePicker(picker) : openPicker(picker)
            return
        }

        const previous = event.target.closest('[data-ui-date-picker-previous]')
        const next = event.target.closest('[data-ui-date-picker-next]')
        if (previous || next) {
            const picker = (previous ?? next).closest('[data-ui-date-picker]')
            if (! picker) return
            const [year, month] = picker.dataset.viewMonth.split('-').map(Number)
            const target = new Date(year, month + (next ? 1 : -1), 1)
            picker.dataset.viewMonth = `${target.getFullYear()}-${target.getMonth()}`
            renderPicker(picker)
            return
        }

        const day = event.target.closest('[data-ui-date-picker-day]')
        if (day) {
            const picker = day.closest('[data-ui-date-picker]')
            if (! picker || day.disabled) return
            syncValue(picker, parseDate(day.dataset.uiDatePickerDay))
            renderPicker(picker)
            return
        }

        const done = event.target.closest('[data-ui-date-picker-done]')
        if (done) {
            const picker = done.closest('[data-ui-date-picker]')
            if (picker) closePicker(picker, true)
            return
        }

        document.querySelectorAll('[data-ui-date-picker]').forEach((picker) => {
            if (! picker.contains(event.target)) closePicker(picker)
        })
    })

    document.addEventListener('input', (event) => {
        if (! (event.target instanceof Element) || ! event.target.matches('[data-ui-date-picker-time]')) return
        const picker = event.target.closest('[data-ui-date-picker]')
        const selected = parseDate(picker?.querySelector('[data-ui-date-picker-value]')?.value)
        if (picker && selected) syncValue(picker, selected)
    })

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || ! (event.target instanceof Element)) return
        const picker = event.target.closest('[data-ui-date-picker]')
        if (picker) closePicker(picker, true)
    })
}
