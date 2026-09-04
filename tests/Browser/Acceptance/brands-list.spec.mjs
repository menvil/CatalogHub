import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    resetBrowserFixture,
    signIn,
} from '../Support/acceptance.mjs'

const metric = (page, slug) => page.locator(`[data-brand-metric="${slug}"]`)

test.beforeAll(() => resetBrowserFixture())

test('CA-011 exposes the persisted operational read model and discovery controls', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await page.getByRole('navigation', { name: 'Central Admin sections' }).getByRole('link', { name: 'Brands', exact: true }).click()

    await expect(page).toHaveURL(/\/admin\/central\/brands$/)
    await expect(page.locator('[data-screen-id="CA-011"][data-fixture-version="brands-list-v3"]')).toBeVisible()
    await expect(metric(page, 'total-brands')).toContainText('27')
    await expect(metric(page, 'active')).toContainText('14')
    await expect(metric(page, 'with-logos')).toContainText('9')
    await expect(metric(page, 'missing-translations')).toContainText('22')
    await expect(metric(page, 'needs-attention')).toContainText('24')

    const acer = page.locator('tr[data-row-id]').filter({ hasText: 'Acer' })
    await expect(acer.locator('img')).toBeVisible()
    await expect(acer).toContainText('3 categories')
    await expect(acer).toContainText('8')
    await expect(acer.getByRole('progressbar', { name: 'Acer translation coverage' })).toHaveAttribute('aria-valuenow', '100')
    await expect(acer.locator('[data-brand-quality="complete"]')).toBeVisible()
    await expect(acer.locator('.brand-list-quality')).toContainText('100%')
    const acerLogo = await acer.locator('[data-logo-state="ready"]').boundingBox()
    expect(acerLogo?.width).toBeGreaterThanOrEqual(64)
    expect(acerLogo?.height).toBeGreaterThanOrEqual(40)
    await expect(acer).not.toContainText('Logo ready')

    const benq = page.locator('tr[data-row-id]').filter({ hasText: 'BenQ' })
    await expect(benq.locator('[data-logo-state="unavailable"]')).toBeVisible()
    await expect(benq.getByText('Logo unavailable', { exact: true })).toBeAttached()
    await expect(benq).toContainText('1 category')
    await expect(benq.getByRole('progressbar', { name: 'BenQ translation coverage' })).toHaveAttribute('aria-valuenow', '0')
    await expect(benq.locator('[data-brand-translation-breakdown]')).toContainText('4 missing')
    await expect(benq.locator('[data-brand-quality="needs_attention"]')).toBeVisible()
    await expect(page.locator('tr[data-row-id]').filter({ hasText: 'Bose' }).locator('[data-logo-state="missing"]')).toBeVisible()

    for (const heading of ['Brand', 'Category Coverage', 'Products', 'Status', 'Translation Coverage', 'Quality', 'Updated', 'Actions']) {
        await expect(page.getByRole('columnheader', { name: new RegExp(`^${heading}`) })).toBeVisible()
    }
    await expect(page.getByRole('columnheader', { name: 'Logo Health' })).toHaveCount(0)
    await expect(page.getByRole('link', { name: 'New Brand', exact: true })).toBeVisible()
    await expect(page.getByText('Import Brands', { exact: true })).toHaveCount(0)
    await expect(page.getByRole('columnheader', { name: /Sites/ })).toHaveCount(0)
    await expect(page.locator('tbody input[type="checkbox"]')).toHaveCount(0)

    assertNoPageErrors()
})

test('CA-011 searches, combines filters, sorts, paginates, and preserves navigation state', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands')

    const search = page.getByRole('searchbox', { name: 'Search', exact: true })
    await search.fill('Samsung')
    await expect(page).toHaveURL(/q=Samsung/)
    await expect(page.getByText('Samsung', { exact: true })).toBeVisible()
    await expect(page.getByText('Sony', { exact: true })).toHaveCount(0)

    await search.fill('apple')
    await expect(page).toHaveURL(/q=apple/)
    await expect(page.getByText('Apple', { exact: true })).toBeVisible()
    await search.fill('Apple Inc.')
    await expect(page).toHaveURL(/q=Apple(?:\+|%20)Inc/)
    await expect(page.getByText('Apple', { exact: true })).toBeVisible()

    await page.goto('/admin/central/brands')
    const country = page.getByRole('combobox', { name: 'Country' })
    await country.fill('Taiwan')
    await page.getByRole('option', { name: 'Taiwan (TW)', exact: true }).click()
    await expect(page).toHaveURL(/country=\d+/)
    await page.locator('#brand-status').selectOption('active')
    await page.locator('#brand-coverage').selectOption('has')
    await page.locator('#brand-translation').selectOption('complete')
    await page.locator('#brand-quality').selectOption('complete')
    await expect(page).toHaveURL(/status=active/)
    await expect(page).toHaveURL(/coverage=has/)
    await expect(page).toHaveURL(/translation=complete/)
    await expect(page).toHaveURL(/quality=complete/)
    await expect(page.getByText('Acer', { exact: true })).toBeVisible()
    await expect(page.getByText('ASUS', { exact: true })).toHaveCount(0)
    await search.fill('Acer')
    await expect(page).toHaveURL(/q=Acer/)
    await expect(page.locator('[data-brand-active-filter-count="6"]')).toContainText('6 active filters')
    await page.getByRole('link', { name: 'Clear filters', exact: true }).click()
    await expect(page).toHaveURL(/\/admin\/central\/brands$/)
    await expect(page.getByRole('link', { name: 'Clear filters', exact: true })).toHaveCount(0)

    await page.getByRole('columnheader', { name: /Products/ }).getByRole('link').click()
    await expect(page).toHaveURL(/sort=products&direction=asc/)
    await page.getByRole('columnheader', { name: /Products/ }).getByRole('link').click()
    await expect(page).toHaveURL(/sort=products&direction=desc/)
    await expect(page.locator('tbody tr[data-row-id]').first()).toContainText('Samsung')

    await page.locator('#brands-per-page').selectOption('20')
    await page.getByRole('link', { name: 'Next page' }).click()
    await expect(page).toHaveURL(/page=2/)
    await page.getByRole('link', { name: 'First page' }).click()

    await search.fill('Apple Inc.')
    await expect(page).toHaveURL(/q=Apple(?:\+|%20)Inc/)
    const appleRow = page.locator('tr[data-row-id]').filter({ hasText: 'Apple' })
    const menuButton = appleRow.locator('summary[aria-label^="Open actions for row"]')
    await menuButton.focus()
    await menuButton.press('ArrowDown')
    await expect(appleRow.getByRole('menuitem', { name: 'View', exact: true })).toBeFocused()
    await appleRow.getByRole('menuitem', { name: 'View', exact: true }).click()
    await expect(page.locator('[data-screen-id="CA-012"]')).toBeVisible()
    await page.goBack()
    await expect(page).toHaveURL(/q=Apple(?:\+|%20)Inc/)
    await expect(page.getByText('Apple Inc.', { exact: true })).toBeVisible()

    const restoredAppleRow = page.locator('tr[data-row-id]').filter({ hasText: 'Apple' })
    await restoredAppleRow.locator('summary[aria-label^="Open actions for row"]').click()
    await restoredAppleRow.getByRole('menuitem', { name: 'Edit', exact: true }).click()
    await expect(page.locator('[data-screen-id="CA-013"]')).toBeVisible()

    assertNoPageErrors()
})

test('CA-011 translation filters are exact and explain every matching row', async ({ page }) => {
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()

    await page.goto('/admin/central/brands?translation=outdated')
    const outdatedRows = page.locator('tr[data-row-id]')
    await expect(page.locator('tr[data-row-id]').filter({ hasText: 'ASUS' })).toBeVisible()
    await expect(page.locator('tr[data-row-id]').filter({ hasText: 'Anker' })).toHaveCount(0)
    for (let index = 0; index < await outdatedRows.count(); index++) {
        await expect(outdatedRows.nth(index).locator('[data-brand-translation-breakdown]')).toContainText('outdated')
    }

    await page.goto('/admin/central/brands?translation=missing')
    await expect(page.locator('tr[data-row-id]').filter({ hasText: 'Anker' })).toContainText('3 missing')
    await expect(page.locator('tr[data-row-id]').filter({ hasText: 'ASUS' })).toHaveCount(0)

    await page.goto('/admin/central/brands?translation=complete')
    await expect(page.locator('tr[data-row-id]').filter({ hasText: 'Acer' })).toBeVisible()
    await expect(page.locator('tr[data-row-id]').filter({ hasText: 'ASUS' })).toHaveCount(0)
})

test('CA-011 filter grid remains contained at intermediate and narrow widths', async ({ page }) => {
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()

    for (const viewport of [
        { width: 1024, height: 900, columns: 3 },
        { width: 768, height: 1024, columns: 2 },
        { width: 390, height: 844, columns: 1 },
    ]) {
        await page.setViewportSize(viewport)
        await page.goto('/admin/central/brands?q=Acer')

        const columns = await page.locator('.brand-list-filters').evaluate((element) =>
            getComputedStyle(element).gridTemplateColumns.split(' ').length,
        )
        expect(columns).toBe(viewport.columns)

        for (const selector of [
            '#brand-search',
            '#brand-country-combobox',
            '#brand-status-trigger',
            '#brand-coverage-trigger',
            '#brand-translation-trigger',
            '#brand-quality-trigger',
        ]) {
            const box = await page.locator(selector).boundingBox()
            expect(box?.x).toBeGreaterThanOrEqual(0)
            expect((box?.x ?? 0) + (box?.width ?? 0)).toBeLessThanOrEqual(viewport.width)
        }

        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    }
})

test('CA-011 remains operational at 390px without page-level overflow', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await page.setViewportSize({ width: 390, height: 844 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands?q=Acer')

    await expect(page.getByRole('heading', { name: 'Brands', exact: true })).toBeVisible()
    await expect(page.getByRole('searchbox', { name: 'Search', exact: true })).toBeVisible()
    const row = page.locator('tr[data-row-id]').filter({ hasText: 'Acer' })
    await expect(row).toHaveCSS('display', 'grid')
    await expect(row.locator('[data-logo-state="ready"]')).toBeVisible()
    await expect(row).toContainText('Active')
    await expect(row).toContainText('100%')
    await expect(row).toContainText('Complete')
    await expect(row.locator('.brand-list-products-cell')).toHaveAttribute('data-mobile-label', 'Products')
    await expect(row.locator('.brand-list-category-cell')).toHaveAttribute('data-mobile-label', 'Categories')
    await expect(row.locator('summary[aria-label^="Open actions for row"]')).toBeVisible()
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-011 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)

    await page.getByRole('button', { name: 'Open navigation' }).click()
    await expect(page.getByRole('navigation', { name: 'Central Admin sections' }).getByRole('link', { name: 'Brands', exact: true })).toBeVisible()
    await page.getByRole('complementary', { name: 'Central Admin navigation' }).getByLabel('Close navigation').click()

    assertNoPageErrors()
})
