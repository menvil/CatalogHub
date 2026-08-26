import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const source = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/tag-input.js'), 'utf8')
const modalSource = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/modal.js'), 'utf8')

test('tag input supports keyboard and IME-safe add, accessible remove, normalized duplicates, limits, and hidden inputs', () => {
    for (const contract of [
        'event.isComposing',
        "event.key !== 'Enter'",
        'event.preventDefault()',
        'data-ui-tag-input-remove',
        '`Remove ${name}`',
        "hidden.type = 'hidden'",
        "normalize('NFC')",
        'toLocaleLowerCase()',
        'currentChips().length >= max',
        '`Maximum ${max} tags.`',
        'That tag is already added.',
        'data-ui-tag-input-reset-values',
        "document.addEventListener('admin:modal-closed'",
    ]) {
        assert.ok(source.includes(contract), `Missing tag-input contract: ${contract}`)
    }

    assert.ok(modalSource.includes("new CustomEvent('admin:modal-closed'"), 'Modal close must publish the generic reset event')
})
