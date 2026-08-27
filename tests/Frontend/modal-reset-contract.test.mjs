import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const modalSource = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/modal.js'), 'utf8')
const selectSource = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/select.js'), 'utf8')

test('modal close restores persisted generic form controls and clears validation state', () => {
    for (const contract of [
        'admin:modal-closed',
        'data-admin-modal-reset-value',
        'data-admin-modal-reset-error',
        'removeAttribute(\'aria-invalid\')',
        "new Event('input', { bubbles: true })",
        "new Event('change', { bubbles: true })",
    ]) {
        assert.ok(modalSource.includes(contract), `Missing modal reset contract: ${contract}`)
    }

    assert.ok(selectSource.includes('syncNativeSelect'), 'Custom selects must reflect a restored native value')
    assert.ok(selectSource.includes("event.target.matches('[data-ui-select-native]')"))
})
