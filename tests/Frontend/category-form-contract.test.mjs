import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const root = resolve(import.meta.dirname, '../..')
const form = readFileSync(resolve(root, 'resources/views/central-admin/categories/_form.blade.php'), 'utf8')
const impact = readFileSync(resolve(root, 'resources/views/central-admin/categories/_slug-impact.blade.php'), 'utf8')
const script = readFileSync(resolve(root, 'resources/js/admin/category-form.js'), 'utf8')
const app = readFileSync(resolve(root, 'resources/js/app.js'), 'utf8')

test('CA-018 script is booted and only enhances hooks rendered by the canonical form', () => {
    assert.match(app, /import \{ bootCategoryForm \} from '\.\/admin\/category-form'/)
    assert.match(app, /bootCategoryForm\(\);/)
    for (const hook of ['data-category-scope-revisions', 'data-category-destination-revision', 'data-category-slug-impact-container', 'data-category-order-parent-note', 'data-category-name', 'data-category-slug']) {
        assert.ok(form.includes(hook), `Form must render ${hook}`)
        assert.ok(script.includes(hook), `Script must use ${hook}`)
    }
    assert.ok(script.includes("select[name=\"parent_id\"]") && script.includes("select[name=\"display_position\"]"))
})

test('CA-018 never renders unsupported prototype controls or lifecycle inputs', () => {
    for (const absent of ['name="status"', 'name="schema_status"', 'name="description"', 'name="position"', 'type="file"', 'Category Code', 'color-input', 'role="tab"']) {
        assert.ok(!form.includes(absent), `Unsupported control rendered: ${absent}`)
    }
    assert.ok(impact.includes('name="slug_impact_fingerprint"'))
    assert.ok(impact.includes('No automatic HTTP redirect is created.'))
})

test('CA-018 slug suggestion produces canonical URL keys client-side only', async () => {
    globalThis.window = {}
    const { slugify } = await import('../../resources/js/admin/category-form.js')
    assert.equal(slugify('Webcams & Streaming'), 'webcams-and-streaming')
    assert.equal(slugify('  Écrans 4K / HDR  '), 'ecrans-4k-hdr')
    assert.equal(slugify('---'), '')
    assert.equal(slugify('x'.repeat(300)).length, 255)
})
