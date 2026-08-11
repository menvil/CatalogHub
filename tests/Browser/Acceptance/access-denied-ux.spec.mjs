import { expect, test } from '@playwright/test'
import {
    captureAcceptanceScreenshot,
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

test('Central persona receives the Site Admin forbidden boundary without site leakage', async ({ page }, testInfo) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    assertNoPageErrors()

    const response = await page.goto('/admin/site')
    expect(response?.status()).toBe(403)
    await expect(page.locator('[data-presentation-context="site-admin"][data-admin-error="403"]')).toBeVisible()
    await expect(page.getByText('Tech Germany')).toHaveCount(0)
    await expect(page.getByText('Monitors Germany')).toHaveCount(0)
    await captureAcceptanceScreenshot(page, testInfo, 'site-access-denied')
})

test('Site persona receives the Central Admin forbidden boundary without central content', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'site', foundationDemo.siteAdmin)
    await expect(page.locator('[data-screen-id="SA-001"]')).toBeVisible()
    assertNoPageErrors()

    const response = await page.goto('/admin/central')
    expect(response?.status()).toBe(403)
    await expect(page.locator('[data-presentation-context="central-admin"][data-admin-error="403"]')).toBeVisible()
    await expect(page.locator('[data-screen-id="CA-001"]')).toHaveCount(0)
})

test('Disabled persona cannot establish an admin session', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.disabled)

    await expect(page).toHaveURL(/\/admin\/central\/login$/)
    await expect(page.locator('[data-auth-screen="central-login"]')).toBeVisible()
    await expect(page.locator('[data-screen-id="CA-001"]')).toHaveCount(0)
    assertNoPageErrors()
})
