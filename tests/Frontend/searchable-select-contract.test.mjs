import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const source = readFileSync(resolve(import.meta.dirname, '../../resources/js/admin/searchable-select.js'), 'utf8')

test('searchable select implements its keyboard and form-control contract', () => {
    for (const key of ['ArrowDown', 'ArrowUp', 'Enter', 'Escape']) {
        assert.match(source, new RegExp(`event\\.key === '${key}'`))
    }

    for (const contract of [
        'aria-activedescendant',
        'aria-expanded',
        'data-ui-searchable-select-native',
        'data-ui-searchable-select-option',
        "new Event('change', { bubbles: true })",
        'dataset.searchUrl',
        'AbortController',
        'fetch(url',
        'url.searchParams.set',
    ]) {
        assert.ok(source.includes(contract), `Missing searchable-select contract: ${contract}`)
    }
})
