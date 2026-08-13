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
    const [shellHeaderHeight, sidebarHeaderHeight] = await Promise.all([
        page.locator('[data-admin-shell-header]').evaluate((element) => element.getBoundingClientRect().height),
        page.locator('[data-admin-sidebar-header]').evaluate((element) => element.getBoundingClientRect().height),
    ])
    expect(shellHeaderHeight).toBe(sidebarHeaderHeight)

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
    await page.locator('[data-ui-select-trigger]').first().click()
    await expect(page.locator('[data-ui-select-menu]').first()).toBeVisible()
    await page.getByRole('option', { name: 'Archived', exact: true }).first().click()
    await expect(page.locator('#gallery-status')).toHaveValue('archived')
    await page.locator('[data-ui-checkbox-list="gallery-market-checkboxes"] label').last().click()
    await expect(page.locator('#gallery-market-checkboxes-2')).toBeChecked()
    await page.locator('[data-ui-checkbox-dropdown="gallery-market-dropdown"] summary').click()
    await expect(page.locator('[data-ui-checkbox-dropdown="gallery-market-dropdown"] [role="listbox"]')).toBeVisible()
    await page.locator('#gallery-market-dropdown-2').check()
    await expect(page.locator('[data-ui-checkbox-dropdown-count]')).toHaveText('3')
    await page.locator('#gallery-market-scroll-4').check()
    await expect(page.locator('#gallery-market-scroll-4')).toBeChecked()
    await page.locator('#gallery-publish-date-trigger').click()
    await expect(page.locator('#gallery-publish-date-panel')).toBeVisible()
    await page.locator('#gallery-publish-date-panel [data-ui-date-picker-day="2026-08-13"]').click()
    await expect(page.locator('#gallery-publish-date')).toHaveValue('2026-08-13')
    await page.locator('#gallery-publish-date-panel [data-ui-date-picker-done]').click()
    for (const selector of [
        '[data-ui-select-trigger]',
        '[data-ui-toggle-hit-area]',
        '[data-ui-date-picker-trigger]',
        '[data-ui-date-picker-trigger] [data-foundation-icon="calendar-days"]',
        '[data-ui-checkbox-dropdown] summary',
        '[data-ui-scrollable-checkbox-list] label',
        '[data-ui-checkbox-list="gallery-market-checkboxes"] label',
    ]) {
        await expect(page.locator(selector).first()).toHaveCSS('cursor', 'pointer')
    }
    await captureAcceptanceScreenshot(page, testInfo, 'central-component-gallery')

    await page.setViewportSize({ width: 390, height: 844 })
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'Component Gallery must settle without horizontal page overflow at 390px.' },
    ).toBe(true)
    await captureAcceptanceScreenshot(page, testInfo, 'central-component-gallery-mobile')

    await page.setViewportSize({ width: 360, height: 900 })
    await page.goto('/admin/central/component-gallery?mode=components&section=forms')
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'Form controls must not introduce horizontal page overflow at 360px.' },
    ).toBe(true)
    await captureAcceptanceScreenshot(page, testInfo, 'central-component-gallery-forms-mobile')

    for (const section of ['actions', 'forms', 'tables', 'indicators', 'layout', 'feedback', 'overlays', 'advanced']) {
        await page.goto(`/admin/central/component-gallery?mode=components&section=${section}`)
        await expect.poll(
            () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
            { message: `Gallery section ${section} must not overflow at 360px.` },
        ).toBe(true)
        const navigationBounds = await page.getByRole('group', { name: 'Gallery sections' }).evaluate((group) => {
            const first = group.firstElementChild?.getBoundingClientRect()
            const last = group.lastElementChild?.getBoundingClientRect()
            const container = group.getBoundingClientRect()
            return first && last
                ? { firstLeft: first.left, lastRight: last.right, containerLeft: container.left, containerRight: container.right }
                : null
        })
        expect(navigationBounds).not.toBeNull()
        expect(navigationBounds.firstLeft).toBeGreaterThanOrEqual(navigationBounds.containerLeft)
        expect(navigationBounds.lastRight).toBeLessThanOrEqual(navigationBounds.containerRight)
    }

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
