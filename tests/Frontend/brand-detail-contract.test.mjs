import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const view = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/show.blade.php'), 'utf8')
const provenanceView = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/partials/external-identities-card.blade.php'), 'utf8')
const composedView = `${view}\n${provenanceView}`
const css = readFileSync(resolve(import.meta.dirname, '../../resources/css/central-admin.css'), 'utf8')

test('CA-012 composes real Brand overview regions without prototype-only domains', () => {
    for (const region of ['brand-identity', 'quality-completeness', 'quality-issues', 'usage', 'recent-products', 'classification', 'external-identities', 'lifecycle', 'record-metadata']) {
        assert.match(composedView, new RegExp(`data-screen-region="${region}"`))
    }

    assert.doesNotMatch(view, /Canonical identity/)
    assert.doesNotMatch(view, />Published<|>Synced<|Sites tab|Site coverage|Hero banner/)
    assert.match(view, /CentralBrandQualityIssueCode::TranslationOutdated/)
    assert.match(view, /filament\.central\.resources\.central-products\.view/)
})

test('CA-012 defines deliberate desktop and mobile region order', () => {
    assert.match(css, /grid-template-areas:\s*'profile'\s*'health'\s*'issues'\s*'portfolio'\s*'products'\s*'classification'\s*'provenance'\s*'lifecycle'\s*'record'/)
    assert.match(css, /@media \(width >= 80rem\)[\s\S]*'profile health'[\s\S]*'portfolio issues'[\s\S]*'products classification'/)
    assert.match(view, /brand-detail-logo[\s\S]*object-contain/)
})
