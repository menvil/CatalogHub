import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

test('CA-011 supports read-only brand discovery without browser errors', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await page.getByRole('navigation', { name: 'Central Admin sections' }).getByRole('link', { name: 'Brands', exact: true }).click()

    await expect(page).toHaveURL(/\/admin\/central\/brands$/)
    await expect(page.getByRole('heading', { name: 'Brands', exact: true })).toBeVisible()
    await expect(page.getByText('Canonical brands used across the central catalog.')).toBeVisible()
    await expect(page.locator('[data-screen-id="CA-011"]')).toBeVisible()
    await expect(page.getByText('Samsung', { exact: true })).toBeVisible()
    await expect(page.getByText('Logitech', { exact: true })).toBeVisible()

    const search = page.getByRole('searchbox', { name: 'Search brands', exact: true })
    await search.fill('Samsung')
    await page.getByRole('button', { name: 'Apply', exact: true }).click()
    await expect(page.getByText('Samsung', { exact: true })).toBeVisible()
    await expect(page.getByText('Sony', { exact: true })).toHaveCount(0)
    await search.fill('')
    await page.getByRole('button', { name: 'Apply', exact: true }).click()
    await expect(page.getByText('Logitech', { exact: true })).toBeVisible()

    await page.locator('#brand-status').selectOption('archived')
    await page.getByRole('button', { name: 'Apply filters' }).click()
    await expect(page.getByText('Sony', { exact: true })).toBeVisible()
    await expect(page.getByText('Samsung', { exact: true })).toHaveCount(0)

    await page.getByRole('link', { name: 'Clear all', exact: true }).click()
    await expect(page.getByText('Samsung', { exact: true })).toBeVisible()

    await page.getByRole('link', { name: 'Next' }).click()
    await expect(page.getByText('Xiaomi', { exact: true })).toBeVisible()
    await expect(page.getByText('Acer', { exact: true })).toHaveCount(0)
    await page.getByRole('link', { name: 'Previous' }).click()

    const nameSort = page.getByRole('columnheader', { name: /Name/ }).getByRole('link')
    await nameSort.click()
    await expect(page.getByText('Xiaomi', { exact: true })).toBeVisible()

    await page.setViewportSize({ width: 1920, height: 1080 })
    const wideTable = await page.locator('[data-screen-region="brands-table"]').evaluate((element) => element.getBoundingClientRect().width)
    expect(wideTable).toBeGreaterThan(1500)

    for (const action of ['Create Brand', 'Edit', 'Archive', 'Restore', 'Activate', 'Delete']) {
        await expect(page.getByRole('button', { name: action, exact: true })).toHaveCount(0)
        await expect(page.getByRole('link', { name: action, exact: true })).toHaveCount(0)
    }

    assertNoPageErrors()
})

test('CA-011 remains usable at 390px without page-level overflow', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await page.setViewportSize({ width: 390, height: 844 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands')

    await expect(page.getByRole('heading', { name: 'Brands', exact: true })).toBeVisible()
    await expect(page.getByRole('searchbox', { name: 'Search brands', exact: true })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Name' })).toBeVisible()
    await expect(page.getByRole('columnheader', { name: 'Status' })).toBeVisible()
    await expect(page.getByText('Samsung', { exact: true })).toBeVisible()
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-011 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)

    await page.getByRole('button', { name: 'Open navigation' }).click()
    await expect(page.getByRole('navigation', { name: 'Central Admin sections' }).getByRole('link', { name: 'Brands', exact: true })).toBeVisible()
    await page.getByRole('complementary', { name: 'Central Admin navigation' }).getByLabel('Close navigation').click()

    await page.getByRole('button', { name: 'Filters', exact: true }).click()
    await expect(page.locator('#brand-status-trigger')).toBeVisible()

    assertNoPageErrors()
})
