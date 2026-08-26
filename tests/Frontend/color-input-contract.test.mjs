import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const source = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/color-input.js'), 'utf8')

test('color input synchronizes canonical text and native picker controls', () => {
    for (const contract of [
        'data-ui-color-input-text',
        'data-ui-color-input-picker',
        'HEX_COLOR.test(text.value)',
        'picker.value = text.value',
        'picker.value.toUpperCase()',
        "new Event('input', { bubbles: true })",
    ]) {
        assert.ok(source.includes(contract), `Missing color-input contract: ${contract}`)
    }
})
