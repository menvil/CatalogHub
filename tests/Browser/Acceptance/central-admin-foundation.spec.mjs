import { expect, test } from '@playwright/test'
import {
    captureAcceptanceScreenshot,
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

test('Central Admin foundation flow covers login, shell, gallery, user menu, and logout', async ({ page }, testInfo) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await page.setViewportSize({ width: 1440, height: 1000 })

    await expect(page).toHaveURL(/\/admin\/central(?:\/|$)/)
    await expect(page.locator('[data-presentation-context="central-admin"]')).toBeVisible()
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await expect(page.getByRole('navigation', { name: 'Central Admin sections' })).toContainText('Dashboard')
    await expect(page.locator('[data-site-shell]')).toHaveCount(0)
    await expect(page.locator('[data-admin-workspace]')).toBeVisible()

    const desktopWorkspace = await page.locator('[data-admin-workspace]').evaluate((element) => element.getBoundingClientRect().width)
    expect(desktopWorkspace).toBeGreaterThan(1050)

    await page.locator('[data-central-user-menu] summary').click()
    await expect(page.locator('[data-central-user-menu]')).toContainText(foundationDemo.centralAdmin)
    await captureAcceptanceScreenshot(page, testInfo, 'central-dashboard')

    await page.goto('/admin/central/component-gallery')
    await expect(page.locator('[data-screen-id="CA-DS-002"]')).toBeVisible()
    await expect(page.locator('[data-admin-components-section="catalog"]')).toBeVisible()
    for (const heading of [
        'Buttons & Actions',
        'Form Controls',
        'Tables',
        'Status / Data Indicators',
        'Layout',
        'Feedback',
        'Overlays',
    ]) {
        await expect(page.getByRole('heading', { name: heading, exact: true })).toBeVisible()
    }
    await expect(page.locator('[data-admin-localized-field-editor]')).toBeVisible()
    await expect(page.locator('[data-admin-media-picker]')).toBeVisible()
    await expect(page.locator('[data-admin-diff-viewer]').first()).toBeVisible()
    await captureAcceptanceScreenshot(page, testInfo, 'central-component-gallery')

    await page.setViewportSize({ width: 390, height: 844 })
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    await captureAcceptanceScreenshot(page, testInfo, 'central-component-gallery-mobile')

    await page.setViewportSize({ width: 1920, height: 1080 })
    await page.goto('/admin/central')
    const wideWorkspace = await page.locator('[data-admin-workspace]').evaluate((element) => element.getBoundingClientRect().width)
    expect(wideWorkspace).toBeGreaterThan(1500)
    await captureAcceptanceScreenshot(page, testInfo, 'central-dashboard-wide')

    await page.locator('[data-central-user-menu] summary').click()
    await page.getByRole('button', { name: 'Log out' }).click()
    await expect(page).toHaveURL(/\/admin\/central\/login$/)
    await expect(page.locator('[data-auth-screen="central-login"]')).toBeVisible()
    assertNoPageErrors()
})

test('Site-only persona cannot enter Central Admin', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.noAccess)

    await expect(page).toHaveURL(/\/admin\/central\/login$/)
    await expect(page.locator('[data-presentation-context="central-admin"]')).toBeVisible()
    await expect(page.locator('[data-screen-id="CA-001"]')).toHaveCount(0)
    assertNoPageErrors()
})
