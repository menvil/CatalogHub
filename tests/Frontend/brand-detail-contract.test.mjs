import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const view = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/show.blade.php'), 'utf8')
const provenanceView = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/partials/external-identities-card.blade.php'), 'utf8')
const composedView = `${view}\n${provenanceView}`
const css = readFileSync(resolve(import.meta.dirname, '../../resources/css/central-admin.css'), 'utf8')

test('CA-012 composes real Brand overview regions without prototype-only domains', () => {
    for (const region of ['brand-identity', 'record-metadata', 'quality-completeness', 'quality-issues', 'usage', 'classification', 'external-identities']) {
        assert.match(composedView, new RegExp(`data-screen-region="${region}"`))
    }

    assert.doesNotMatch(view, /Canonical identity/)
    assert.doesNotMatch(view, />Brand profile<|>Product portfolio<|>Brand health<|data-screen-region="lifecycle"|class="brand-detail-(?:lifecycle|record|portfolio|health) min-w-0"/)
    assert.doesNotMatch(view, />Published<|>Synced<|Sites tab|Site coverage|Hero banner/)
    assert.match(view, /CentralBrandQualityIssueCode::TranslationOutdated/)
    assert.doesNotMatch(view, /data-screen-region="recent-products"|data-brand-recent-products/)
    assert.match(view, /brand-detail-heading-actions[\s\S]*Edit Brand[\s\S]*Archive Brand/)
})

test('CA-012 defines deliberate desktop and mobile region order', () => {
    assert.match(view, /data-brand-detail-main[\s\S]*brand-detail-profile[\s\S]*brand-detail-classification[\s\S]*brand-detail-provenance/)
    assert.match(view, /data-brand-detail-rail[\s\S]*brand-detail-issues/)
    assert.match(css, /\.brand-detail-profile \{ order: 1; \}[\s\S]*\.brand-detail-issues \{ order: 2; \}[\s\S]*\.brand-detail-provenance \{ order: 3; \}/)
    assert.match(css, /@media \(width >= 80rem\)[\s\S]*\.brand-detail-main,[\s\S]*\.brand-detail-rail \{[\s\S]*display: flex;/)
    assert.match(view, /brand-detail-logo-column[\s\S]*brand-detail-logo[\s\S]*object-contain[\s\S]*Manage logo[\s\S]*brand-detail-profile-fields/)
    assert.match(view, /brand-detail-profile-field--terminal[\s\S]*Contact email/)
    assert.match(css, /\.brand-detail-profile-field--terminal \{[\s\S]*border-bottom: 0;/)
    assert.match(view, /brand-detail-profile[\s\S]*brand-detail-profile-fields[\s\S]*brand-detail-classification[\s\S]*Brand summary[\s\S]*data-screen-region="quality-completeness"[\s\S]*data-screen-region="translation-summary"[\s\S]*data-screen-region="record-metadata"[\s\S]*brand-detail-provenance/)
})

test('CA-012 consolidates summary and health while Classification stays inside identity', () => {
    const summary = view.slice(view.indexOf('brand-detail-overview-summary'), view.indexOf('brand-detail-provenance'))
    const classification = view.slice(view.indexOf('brand-detail-classification'), view.indexOf('brand-detail-overview-summary'))

    assert.match(summary, /data-products-count[\s\S]*data-brand-quality-score[\s\S]*Translation coverage[\s\S]*Record ID/)
    assert.doesNotMatch(summary, /data-brand-derived-categories|brand-detail-category-chip/)
    assert.match(classification, /data-brand-derived-categories/)
    assert.match(classification, /data-brand-tags/)
    assert.doesNotMatch(view, /brand-detail-middle-grid|brand-detail-portfolio|brand-detail-health/)
    assert.equal(view.match(/data-products-count=/g)?.length, 1)
    assert.equal(view.match(/data-brand-quality-score=/g)?.length, 1)
})
