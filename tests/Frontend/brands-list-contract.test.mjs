import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const view = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/index.blade.php'), 'utf8')
const css = readFileSync(resolve(import.meta.dirname, '../../resources/css/central-admin.css'), 'utf8')

test('CA-011 separates translation, quality, and canonical logo identity', () => {
    for (const contract of [
        'data-brand-translation-breakdown',
        'brand-list-quality-cell',
        'data-brand-quality',
        'brand-list-logo-shell',
        'Logo unavailable',
    ]) {
        assert.ok(view.includes(contract), `Missing CA-011 presentation contract: ${contract}`)
    }

    assert.ok(!view.includes('Logo Health'), 'CA-011 must not expose a technical Logo Health column')
    assert.ok(!view.includes('Logo ready'), 'A ready canonical logo explains itself')
})

test('CA-011 exposes one global clear contract and explicit responsive grids', () => {
    assert.equal((view.match(/>Clear filters</g) ?? []).length, 1)
    assert.ok(view.includes('data-brand-active-filter-count'))
    assert.ok(!view.includes('data-brand-list-clear-country'))

    for (const contract of [
        '@media (width >= 40rem)',
        '@media (width >= 56.25rem)',
        '@media (width >= 80rem)',
        '@media (width < 40rem)',
        'grid-template-columns: repeat(3, minmax(0, 1fr))',
        'grid-template-columns: repeat(2, minmax(0, 1fr))',
        'content: attr(data-mobile-label)',
    ]) {
        assert.ok(css.includes(contract), `Missing CA-011 responsive contract: ${contract}`)
    }
})
