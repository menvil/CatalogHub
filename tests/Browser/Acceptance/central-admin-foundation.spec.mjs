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

    await expect(page).toHaveURL(/\/admin\/central(?:\/|$)/)
    await expect(page.locator('[data-presentation-context="central-admin"]')).toBeVisible()
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await expect(page.getByRole('navigation', { name: 'Central Admin sections' })).toContainText('Dashboard')
    await expect(page.locator('[data-site-shell]')).toHaveCount(0)

    await page.locator('[data-central-user-menu] summary').click()
    await expect(page.locator('[data-central-user-menu]')).toContainText(foundationDemo.centralAdmin)
    await captureAcceptanceScreenshot(page, testInfo, 'central-dashboard')

    await page.goto('/admin/central/component-gallery')
    await expect(page.locator('[data-screen-id="CA-DS-001"]')).toBeVisible()
    await expect(page.locator('[data-gallery-fixture]')).toBeVisible()
    await captureAcceptanceScreenshot(page, testInfo, 'central-component-gallery')

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
