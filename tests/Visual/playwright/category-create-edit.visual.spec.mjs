import { expect, test } from '@playwright/test'
import { createHash } from 'node:crypto'
import { mkdirSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { foundationDemo, observePageErrors, resetBrowserFixture, signIn } from '../../Browser/Support/acceptance.mjs'

// CA-018 implementation candidates awaiting Product Owner review. They are never
// written to tests/Visual/baselines and are not compared against approved files.
const monitors = 196009
const legacy = 196003
const televisions = 196008
const freeze = `*, *::before, *::after { animation-duration: 0s !important; transition-duration: 0s !important; caret-color: transparent !important; } html { scrollbar-width: none !important; } ::-webkit-scrollbar { display: none !important; }`

test.beforeAll(() => resetBrowserFixture('category-create-edit-v1'))
test.afterAll(() => resetBrowserFixture())

async function open(page, path, width, height) {
    await page.setViewportSize({ width, height })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(path)
    await expect(page.locator('[data-screen-id="CA-018"][data-fixture-version="category-create-edit-v1"]')).toBeVisible()
    await settle(page)
}

async function settle(page) {
    await page.evaluate(() => document.fonts.ready)
    await page.addStyleTag({ content: freeze })
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
}

for (const [width, height] of [[1440, 1000], [390, 844]]) {
    test(`CA-018 create ${width}px candidate evidence awaiting Product Owner review`, async ({ page }, testInfo) => {
        const noErrors = observePageErrors(page)
        await open(page, '/admin/central/categories/create', width, height)
        await captureCandidate(page, testInfo, 'create', width, height)
        noErrors()
    })
}

for (const [width, height] of [[1440, 1000], [1024, 900], [768, 1024], [390, 844]]) {
    test(`CA-018 edit ${width}px candidate evidence awaiting Product Owner review`, async ({ page }, testInfo) => {
        const noErrors = observePageErrors(page)
        await open(page, `/admin/central/categories/${monitors}/edit`, width, height)
        await captureCandidate(page, testInfo, 'edit', width, height)
        if (width === 390) {
            await page.locator('[data-category-form-actions]').scrollIntoViewIfNeeded()
            await expect(page.getByRole('button', { name: 'Save Category', exact: true })).toBeInViewport()
            await captureCandidate(page, testInfo, 'mobile-actions', width, height)
        }
        noErrors()
    })
}

test('CA-018 parent selector, slug impact, validation and archived candidates awaiting Product Owner review', async ({ page }, testInfo) => {
    const noErrors = observePageErrors(page)
    await open(page, `/admin/central/categories/${monitors}/edit`, 1440, 1000)
    await page.getByRole('combobox', { name: 'Parent Category' }).fill('Home')
    await expect(page.locator('#category-parent-listbox')).toBeVisible()
    await captureCandidate(page, testInfo, 'parent-selector', 1440, 1000)

    await page.goto(`/admin/central/categories/${monitors}/edit`)
    await page.getByLabel('Slug').fill('computer-monitors')
    await expect(page.locator('[data-category-slug-impact]')).toBeVisible()
    await settle(page)
    await page.locator('[data-screen-region="slug-impact"]').evaluate((element) => element.scrollIntoView({ block: 'start' }))
    await captureCandidate(page, testInfo, 'slug-impact', 1440, 1000)

    await page.goto(`/admin/central/categories/${monitors}/edit`)
    await page.getByLabel('Category Name').fill('')
    await page.locator('#category-form').evaluate((form) => form.setAttribute('novalidate', 'novalidate'))
    await page.getByRole('button', { name: 'Save Category', exact: true }).click()
    await expect(page.locator('#category-name-error')).toBeVisible()
    await settle(page)
    await captureCandidate(page, testInfo, 'validation-error', 1440, 1000)

    await page.goto(`/admin/central/categories/${legacy}/edit`)
    await expect(page.locator('[data-category-lifecycle]')).toContainText('Archived')
    await settle(page)
    await captureCandidate(page, testInfo, 'archived', 1440, 1000)
    noErrors()
})

// Runs last: it commits a real concurrent reorder to produce the stale-revision state.
test('CA-018 stale revision candidate awaiting Product Owner review', async ({ page, context }, testInfo) => {
    const noErrors = observePageErrors(page)
    await open(page, `/admin/central/categories/${monitors}/edit`, 1440, 1000)
    const other = await context.newPage()
    await other.goto(`/admin/central/categories/${televisions}/edit`)
    await other.locator('#category-display-position-trigger').click()
    await other.locator('#category-display-position-menu').getByRole('option', { name: '3 of 3 — after Projectors', exact: true }).click()
    await other.getByRole('button', { name: 'Save Category', exact: true }).click()
    await expect(other.getByText('Category updated.').first()).toBeVisible()
    await other.close()
    await page.locator('#category-display-position-trigger').click()
    await page.locator('#category-display-position-menu').getByRole('option', { name: '1 of 3 — before Televisions', exact: true }).click()
    await page.getByRole('button', { name: 'Save Category', exact: true }).click()
    await expect(page.locator('[data-category-form-errors]')).toContainText('The sibling scope changed. Reload before saving.')
    await settle(page)
    await captureCandidate(page, testInfo, 'stale-revision', 1440, 1000)
    noErrors()
})

async function captureCandidate(page, testInfo, state, width, height) {
    const directory = resolve('storage/logs/visual-artifacts/ca-018-candidates')
    mkdirSync(directory, { recursive: true })
    const path = resolve(directory, `ca-018__${state}__${width}x${height}.png`)
    const bytes = await page.screenshot({ path, animations: 'disabled', scale: 'css' })
    const checksum = createHash('sha256').update(bytes).digest('hex')
    writeFileSync(`${path}.sha256`, `${checksum}\n`)
    writeFileSync(`${path}.json`, JSON.stringify({ screen_id: 'CA-018', fixture: 'category-create-edit-v1', state, viewport: `${width}x${height}`, sha256: checksum, approval: 'pending-product-owner-review', source: 'categories-schema-prototype-v1', source_sha256: '91008de57b8a2f0e8a59761d907606caa68aaa12ca7c942e16bb487e466268dd' }, null, 2) + '\n')
    await testInfo.attach(`CA-018 ${state} ${width}px pending review`, { path, contentType: 'image/png' })
}
