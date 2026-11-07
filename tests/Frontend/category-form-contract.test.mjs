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

function fakeSlugImpactForm(currentSlug) {
    const listeners = {}
    const slug = { value: currentSlug, addEventListener: (type, listener) => { listeners[type] = listener } }
    const attributes = {}
    const container = {
        innerHTML: '',
        setAttribute: (name, value) => { attributes[name] = value },
        removeAttribute: (name) => { delete attributes[name] },
        attributes,
    }
    const hooks = { '[data-category-slug]': slug, '[data-category-slug-impact-container]': container }
    const form = { dataset: { categoryForm: 'edit', slugImpactUrl: '/impact', currentSlug }, querySelector: (selector) => hooks[selector] ?? null }
    let timer = null
    globalThis.window = { setTimeout: (callback) => { timer = callback; return 1 }, clearTimeout: () => {} }
    globalThis.document = { querySelectorAll: () => [form] }

    return {
        container,
        // Types a value and lets the debounce fire immediately.
        type(value) {
            slug.value = value
            listeners.input()
            const pending = timer
            timer = null
            return pending()
        },
    }
}

function deferred() {
    let resolve
    const promise = new Promise((done) => { resolve = done })
    return { promise, resolve }
}

test('CA-018 slug preview never lets a slower response replace a newer slug preview', async () => {
    const { bootCategoryForm } = await import('../../resources/js/admin/category-form.js')
    const form = fakeSlugImpactForm('desktop-monitors')
    const requests = []
    globalThis.fetch = (url) => {
        const response = deferred()
        requests.push({ url, response })
        return response.promise
    }
    bootCategoryForm()

    const first = form.type('older-slug')
    const slowBody = deferred()
    requests[0].response.resolve({ ok: true, redirected: false, text: () => slowBody.promise })
    await new Promise((done) => setImmediate(done))
    const second = form.type('newer-slug')
    requests[1].response.resolve({ ok: true, redirected: false, text: async () => '<p>newer-slug impact</p>' })
    await second
    slowBody.resolve('<p>older-slug impact</p>')
    await first

    assert.deepEqual(requests.map(({ url }) => url), ['/impact?slug=older-slug', '/impact?slug=newer-slug'])
    assert.equal(form.container.innerHTML, '<p>newer-slug impact</p>')
    assert.equal(form.container.attributes['aria-busy'], undefined)
})

test('CA-018 slug preview clears a stale fragment and busy state when the slug is current, empty or too long', async () => {
    const { bootCategoryForm } = await import('../../resources/js/admin/category-form.js')
    const form = fakeSlugImpactForm('desktop-monitors')
    const pending = []
    globalThis.fetch = () => new Promise((resolve) => pending.push(resolve))
    bootCategoryForm()

    for (const value of [' desktop-monitors ', '', 'x'.repeat(256)]) {
        const inFlight = form.type('reviewed-slug')
        form.container.innerHTML = '<p>reviewed-slug impact</p>'
        await form.type(value)
        assert.equal(form.container.innerHTML, '', `A fragment survived the value ${JSON.stringify(value.slice(0, 20))}`)
        assert.equal(form.container.attributes['aria-busy'], undefined)
        pending.shift()({ ok: true, redirected: false, text: async () => '<p>late reviewed-slug impact</p>' })
        await inFlight
        assert.equal(form.container.innerHTML, '', 'A superseded response restored a stale fragment')
    }
})
