const HEX_COLOR = /^#[0-9a-f]{6}$/i

export function bootColorInputs(root = document) {
    for (const field of root.querySelectorAll('[data-ui-color-input]')) {
        if (field.dataset.uiColorInputReady === 'true') continue

        const text = field.querySelector('[data-ui-color-input-text]')
        const picker = field.querySelector('[data-ui-color-input-picker]')
        if (!(text instanceof HTMLInputElement) || !(picker instanceof HTMLInputElement)) continue

        text.addEventListener('input', () => {
            if (HEX_COLOR.test(text.value)) {
                picker.value = text.value
            }
        })

        picker.addEventListener('input', () => {
            text.value = picker.value.toUpperCase()
            text.dispatchEvent(new Event('input', { bubbles: true }))
        })

        field.dataset.uiColorInputReady = 'true'
    }
}
