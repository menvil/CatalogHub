import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, resetBrowserFixture, signIn } from '../Support/acceptance.mjs'

const primary = '/admin/central/categories/195003'
test.beforeEach(() => resetBrowserFixture('category-detail-v1'))
test.afterAll(() => resetBrowserFixture())

async function openDetail(page) {
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(primary)
    await expect(page.locator('[data-screen-id="CA-017"][data-fixture-version="category-detail-v1"]')).toBeVisible()
}

test('CA-017 opens from CA-016 with real identity, schema, direct usage and activity', async ({ page }) => {
    const noErrors = observePageErrors(page)
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/categories?q=detail-gaming-monitors')
    await page.locator('tr[data-row-id="195003"]').getByRole('link', { name: 'Gaming Monitors', exact: true }).click()
    await expect(page).toHaveURL(new RegExp(primary + '$'))
    await expect(page.locator('[data-category-id]')).toHaveText('195003')
    await expect(page.locator('[data-category-slug]')).toHaveText('detail-gaming-monitors')
    await expect(page.locator('[data-category-parent]')).toHaveText('Displays')
    await expect(page.locator('[data-category-lifecycle]')).toHaveText(/Category:.*Active/)
    await expect(page.locator('[data-category-schema-state]')).toHaveText(/Schema:.*Approved/)
    for (const [key, count] of Object.entries({ sections: 2, attributes: 4, required: 2, facets: 1, comparison: 2, products: 4, sites: 3 })) {
        await expect(page.locator(`[data-detail-count="${key}"]`)).toHaveText(String(count))
    }
    await expect(page.locator('[data-schema-revision]')).toHaveText('6')
    await expect(page.locator('[data-screen-region="site-selection"]')).toContainText('Preview Store')
    await expect(page.locator('[data-screen-region="site-selection"]')).not.toContainText('Archived Store')
    await expect(page.locator('[data-screen-region="site-selection"] a')).toHaveCount(0)
    await expect(page.locator('[data-translation-coverage]')).toHaveText('3/5 covered · 60%')
    await expect(page.locator('[data-screen-region="recent-activity"]')).toContainText('Schema approved')
    await expect(page.locator('[data-screen-region="recent-activity"]')).toContainText('Ada Catalog')
    await expect(page.getByRole('link', { name: 'Edit Category', exact: true })).toHaveAttribute('href', /central-categories\/195003\/edit$/)
    await expect(page.getByRole('link', { name: 'View Schema', exact: true })).toHaveAttribute('href', /central-categories\/195003\/schema$/)
    for (const label of ['Duplicate Category', 'Export Category', 'Comparison Sets', 'Facet Sets', 'Channels', 'View Full Activity']) await expect(page.getByText(label, { exact: true })).toHaveCount(0)
    noErrors()
})

test('CA-017 Locale switching preserves exact status and labels fallback, with accessible copy feedback', async ({ page }) => {
    const noErrors = observePageErrors(page)
    await page.addInitScript(() => Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText: async (value) => { window.__categoryCopied = value } } }))
    await openDetail(page)
    await page.locator('#category-detail-locale').selectOption({ label: 'de-DE' })
    await page.getByRole('button', { name: 'Apply Locale', exact: true }).click()
    await expect(page).toHaveURL(/locale=\d+/)
    await expect(page.locator('[data-selected-translation-status]')).toHaveText('Missing')
    await expect(page.locator('[data-description-provenance]')).toContainText('Showing en-US fallback')
    await expect(page.locator('[data-category-description]')).toContainText('High performance gaming monitors')
    await page.getByRole('button', { name: 'Copy Category ID', exact: true }).focus()
    await page.keyboard.press('Enter')
    await expect.poll(() => page.evaluate(() => window.__categoryCopied)).toBe('195003')
    await expect(page.locator('[data-category-copy-feedback]')).toContainText('copied')
    await page.evaluate(() => { navigator.clipboard.writeText = async () => { throw new Error('Unavailable') } })
    await page.getByRole('button', { name: 'Copy Category slug', exact: true }).click()
    await expect(page.locator('[data-category-copy-feedback]')).toContainText('Could not copy')
    noErrors()
})

test('CA-017 Archive, Restore to Draft and Activate persist and agree with CA-016', async ({ page }) => {
    const noErrors = observePageErrors(page)
    await openDetail(page)
    for (const [command, expected] of [['Archive', 'Archived'], ['Restore', 'Draft'], ['Activate', 'Active']]) {
        await page.locator('[data-page-actions]').getByRole('button', { name: `${command} Category`, exact: true }).click()
        const dialog = page.getByRole('dialog')
        await expect(dialog).toBeVisible()
        if (command === 'Restore') await expect(dialog).toContainText('returns to Draft')
        await dialog.getByRole('button', { name: `${command} Category`, exact: true }).focus()
        await page.keyboard.press('Enter')
        await expect(page).toHaveURL(/\/categories\/195003\?locale=\d+/)
        await expect(page.locator('[data-category-lifecycle]')).toContainText(expected)
        await expect(page.locator('[data-category-schema-state]')).toContainText('Approved')
        await page.reload()
        await expect(page.locator('[data-category-lifecycle]')).toContainText(expected)
        await page.goto('/admin/central/categories?q=detail-gaming-monitors')
        await expect(page.locator('tr[data-row-id="195003"] .category-list-status')).toHaveText(expected)
        await page.locator('tr[data-row-id="195003"]').getByRole('link', { name: 'Gaming Monitors', exact: true }).click()
    }
    noErrors()
})

for (const [width, height] of [[1440, 1000], [1024, 900], [768, 1024], [390, 844]]) {
    test(`CA-017 ${width}px identity, copy, cards, dialog and drawer fit the viewport`, async ({ page }) => {
        const noErrors = observePageErrors(page)
        await page.setViewportSize({ width, height })
        await openDetail(page)
        await expect(page.getByRole('heading', { level: 1, name: 'Gaming Monitors' })).toBeVisible()
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        await page.getByRole('button', { name: 'Copy Category slug', exact: true }).scrollIntoViewIfNeeded()
        await expect(page.getByRole('button', { name: 'Copy Category slug', exact: true })).toBeVisible()
        await page.locator('[data-screen-region="recent-activity"]').scrollIntoViewIfNeeded()
        await expect(page.getByRole('heading', { name: 'Recent Activity', exact: true })).toBeVisible()
        await page.locator('[data-page-actions]').getByRole('button', { name: 'Archive Category', exact: true }).click()
        const bounds = await page.getByRole('dialog').boundingBox()
        expect(bounds.x).toBeGreaterThanOrEqual(0)
        expect(bounds.x + bounds.width).toBeLessThanOrEqual(width)
        await page.keyboard.press('Escape')
        if (width === 390) {
            await page.locator('[data-central-sidebar-open]').first().click()
            await expect(page.getByRole('navigation', { name: 'Central Admin sections' })).toBeVisible()
            await page.keyboard.press('Escape')
        }
        noErrors()
    })
}

test('CA-017 rejects a forbidden actor and nonexistent IDs', async ({ page }) => {
    await signIn(page, 'central', foundationDemo.translator)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    expect((await page.goto(primary)).status()).toBe(403)
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    expect((await page.goto('/admin/central/categories/99999999')).status()).toBe(404)
})
