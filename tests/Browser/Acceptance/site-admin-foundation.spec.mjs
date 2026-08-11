import { expect, test } from '@playwright/test'
import {
    captureAcceptanceScreenshot,
    foundationDemo,
    foundationSites,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

test('Site Admin owner can switch both active site contexts and log out', async ({ page }, testInfo) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'site', foundationDemo.siteAdmin)

    await expect(page.locator('[data-screen-id="SA-001"]')).toBeVisible()
    await expect(page.locator('[data-site-context-header]')).toContainText('Tech Germany')
    await expect(page.locator('[data-site-context-header]')).toContainText('tech-germany.test')
    await expect(page.locator('[data-site-context-header]')).toContainText('de-DE')
    await expect(page.locator('[data-central-shell]')).toHaveCount(0)

    await page.getByText('Switch site', { exact: true }).click()
    await page.getByRole('link', { name: 'Monitors Germany' }).click()
    await expect(page).toHaveURL(new RegExp(`site_id=${foundationSites.monitorsId}`))
    await expect(page.locator('[data-site-context-header]')).toContainText('Monitors Germany')
    await expect(page.locator('[data-site-context-header]')).toContainText('monitors-germany.test')
    await captureAcceptanceScreenshot(page, testInfo, 'site-admin-monitors')

    await page.setViewportSize({ width: 360, height: 800 })
    await expect(page.getByRole('button', { name: 'Open navigation' })).toBeVisible()
    await expect(page.locator('[data-site-context-header]')).toContainText('EUR')
    await captureAcceptanceScreenshot(page, testInfo, 'site-admin-mobile')

    await page.getByRole('button', { name: 'Logout' }).click()
    await expect(page).toHaveURL(/\/admin\/site\/login$/)
    assertNoPageErrors()
})

test('Site Admin cannot select the archived fixture', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'site', foundationDemo.siteAdmin)
    await expect(page.locator('[data-screen-id="SA-001"]')).toBeVisible()
    const response = await page.request.get(`/admin/site?site_id=${foundationSites.archivedId}`)

    expect(response.status()).toBe(403)
    expect(await response.text()).not.toContain('Archived Germany')
    assertNoPageErrors()
})

test('Single-site translator cannot tamper into an unassigned active site', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'site', foundationDemo.translator)
    await expect(page.locator('[data-site-context-header]')).toContainText('Tech Germany')

    const response = await page.request.get(`/admin/site?site_id=${foundationSites.monitorsId}`)
    expect(response.status()).toBe(403)
    expect(await response.text()).not.toContain('Monitors Germany')
    assertNoPageErrors()
})
