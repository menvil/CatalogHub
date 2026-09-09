import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { resolve } from 'node:path'
import test from 'node:test'

const form = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/_form.blade.php'), 'utf8')
const create = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/create.blade.php'), 'utf8')
const edit = readFileSync(resolve(import.meta.dirname, '../../resources/views/central-admin/brands/edit.blade.php'), 'utf8')

test('CA-013 composes one canonical information surface with internal subsections', () => {
    const main = form.slice(form.indexOf('<main'), form.indexOf('</main>'))

    assert.equal((main.match(/<x-admin\.card/g) ?? []).length, 1)
    for (const contract of [
        'data-screen-region="brand-information"',
        'data-screen-region="identity-fields"',
        'data-screen-region="company-origin"',
        'data-screen-region="parent-company"',
        'brand-parent-company-control',
        'data-screen-region="online-presence"',
        'md:grid-cols-2 xl:grid-cols-3',
    ]) {
        assert.ok(form.includes(contract), `Missing CA-013 composition contract: ${contract}`)
    }

    const onlineSection = form.slice(form.indexOf('data-screen-region="online-presence"'), form.indexOf('</main>'))
    assert.ok(onlineSection.includes('id="brand-primary-color"'), 'Primary color must share the final field grid instead of restoring a one-field subsection')
    assert.ok(!form.includes('data-screen-region="visual-identity-fields"'))
    assert.ok(!form.includes('>Visual identity</h3>'))
    assert.ok(!form.includes('sticky bottom-0'), 'CA-013 must not restore the oversized sticky footer')
    assert.ok(!form.includes('data-screen-region="form-actions"'))
})

test('CA-013 keeps save actions in the header and ownership outside scalar submission', () => {
    assert.match(create, /type="submit" form="brand-form">Create Brand/)
    assert.match(edit, /type="submit" form="brand-form">Save changes/)
    assert.ok(edit.includes('route(\'central.brands.ownership.assign\''))
    assert.ok(edit.includes('route(\'central.brands.ownership.create\''))
    assert.ok(edit.includes('route(\'central.brands.ownership.clear\''))
    assert.ok(!form.includes('name="organization_id"'))
    assert.ok(!form.includes('name="status"'))
})

test('CA-013 does not restore prototype-only content and publication domains', () => {
    for (const forbidden of [
        'Tagline',
        'Short Description',
        'Long Description',
        'SEO Title',
        'SEO Meta Description',
        'Category Assignments',
        'Site Visibility',
        'Publish Brand',
        'Preview Brand',
        'Completion Checklist',
        'External Brand ID',
        'Source System',
        'Parent Brand',
    ]) {
        assert.ok(!form.includes(forbidden), `Forbidden CA-013 prototype field restored: ${forbidden}`)
    }
})
