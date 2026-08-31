import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const source = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/brand-translations.js'), 'utf8')

test('copy from source is explicit, overwrite-aware, and only updates the local form control', () => {
    for (const contract of [
        'data-brand-translation-copy-source',
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
