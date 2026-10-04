import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const source = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/brand-translations.js'), 'utf8')
const view = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/translations.blade.php'), 'utf8')

test('draft protection reads the saved-value contract rendered by the editor', () => {
    assert.ok(view.includes('data-brand-translation-saved-value'))
    assert.ok(source.includes('brandTranslationSavedValue'))
    assert.ok(source.includes('Discard unsaved translation changes?'))
})

test('copy from source is explicit, overwrite-aware, and only updates the local form control', () => {
    for (const contract of [
        'data-brand-translation-copy-source',
        'data-brand-translation-copy-all',
        'HTMLTextAreaElement',
        'data-brand-translation-counter',
        'sourceValue.trim()',
        'brandTranslationCopyTarget',
        'brandTranslationSourceValue',
        'window.confirm',
        "new Event('input', { bubbles: true })",
        "new Event('change', { bubbles: true })",
    ]) {
        assert.ok(source.includes(contract), `Missing Brand translation copy contract: ${contract}`)
    }

    for (const forbidden of ['fetch(', 'form.submit(', 'requestSubmit(', 'status.value']) {
        assert.ok(! source.includes(forbidden), `Copy from Source must not persist or change workflow state: ${forbidden}`)
    }
})
