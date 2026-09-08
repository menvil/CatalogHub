import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const view = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/show.blade.php'), 'utf8')
const provenanceView = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/partials/external-identities-card.blade.php'), 'utf8')
const composedView = `${view}\n${provenanceView}`
const css = readFileSync(resolve(import.meta.dirname, '../../resources/css/central-admin.css'), 'utf8')

test('CA-012 composes real Brand overview regions without prototype-only domains', () => {
    for (const region of ['brand-identity', 'record-metadata', 'quality-completeness', 'quality-issues', 'usage', 'recent-products', 'classification', 'external-identities']) {
        assert.match(composedView, new RegExp(`data-screen-region="${region}"`))
    }

    assert.doesNotMatch(view, /Canonical identity/)
    assert.doesNotMatch(view, />Brand profile<|data-screen-region="lifecycle"|class="brand-detail-(?:lifecycle|record) min-w-0"/)
    assert.doesNotMatch(view, />Published<|>Synced<|Sites tab|Site coverage|Hero banner/)
    assert.match(view, /CentralBrandQualityIssueCode::TranslationOutdated/)
    assert.match(view, /filament\.central\.resources\.central-products\.view/)
    assert.match(view, /brand-detail-heading-actions[\s\S]*Edit Brand[\s\S]*Archive Brand/)
})

test('CA-012 defines deliberate desktop and mobile region order', () => {
    assert.match(view, /data-brand-detail-main[\s\S]*brand-detail-profile[\s\S]*brand-detail-middle-grid[\s\S]*brand-detail-portfolio[\s\S]*brand-detail-classification[\s\S]*brand-detail-products[\s\S]*brand-detail-provenance/)
    assert.match(view, /data-brand-detail-rail[\s\S]*brand-detail-health[\s\S]*brand-detail-issues/)
    assert.match(css, /\.brand-detail-profile \{ order: 1; \}[\s\S]*\.brand-detail-health \{ order: 2; \}[\s\S]*\.brand-detail-provenance \{ order: 7; \}/)
    assert.match(css, /@media \(width >= 80rem\)[\s\S]*\.brand-detail-main,[\s\S]*\.brand-detail-rail \{[\s\S]*display: flex;/)
    assert.match(css, /@media \(width >= 80rem\)[\s\S]*\.brand-detail-middle-grid \{[\s\S]*grid-template-columns: repeat\(2/)
    assert.match(view, /brand-detail-logo-column[\s\S]*brand-detail-logo[\s\S]*object-contain[\s\S]*Manage logo[\s\S]*brand-detail-profile-fields/)
    assert.match(view, /brand-detail-profile[\s\S]*data-screen-region="record-metadata"[\s\S]*brand-detail-middle-grid/)
})

test('CA-012 keeps portfolio summary separate from Classification chips', () => {
    const portfolio = view.slice(view.indexOf('brand-detail-portfolio'), view.indexOf('brand-detail-classification'))
    const classification = view.slice(view.indexOf('brand-detail-classification'), view.indexOf('brand-detail-products'))

    assert.doesNotMatch(portfolio, /data-brand-derived-categories|brand-detail-category-chip/)
    assert.match(classification, /data-brand-derived-categories/)
    assert.match(classification, /data-brand-tags/)
})
