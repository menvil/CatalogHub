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
    await page.locator('[data-admin-workspace]').click({ position: { x: 20, y: 20 } })
    await expect(page.locator('[data-central-user-menu]')).not.toHaveAttribute('open', '')
    await captureAcceptanceScreenshot(page, testInfo, 'central-dashboard')

    const brandsResponse = await page.goto('/admin/central/brands')
    expect(brandsResponse?.ok()).toBe(true)
    const brandSearch = page.locator('[data-brand-list-search]')
    const brandRows = page.locator('[data-admin-data-table] tbody tr')
    await brandSearch.fill('Samsung')
    await expect(page).toHaveURL(/q=Samsung/)
    await expect(brandRows).toHaveCount(1)
    await expect(brandRows.first()).toContainText('Samsung')
    await brandSearch.fill('')
    await expect(page).not.toHaveURL(/q=Samsung/)
    await expect(brandSearch).toHaveValue('')
    await expect(brandRows).toHaveCount(20)

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
    const gallerySelect = page.locator('[data-ui-select]').first()
    await gallerySelect.locator('[data-ui-select-native]').evaluate((element) => {
        element.dataset.inputEvents = '0'
        element.dataset.changeEvents = '0'
        element.addEventListener('input', () => { element.dataset.inputEvents = String(Number(element.dataset.inputEvents) + 1) })
        element.addEventListener('change', () => { element.dataset.changeEvents = String(Number(element.dataset.changeEvents) + 1) })
    })
    await gallerySelect.locator('[data-ui-select-trigger]').click()
    await expect(gallerySelect.locator('[data-ui-select-menu]')).toBeVisible()
    await gallerySelect.getByRole('option', { name: 'Archived', exact: true }).click()
    await expect(gallerySelect.locator('[data-ui-select-native]')).toHaveValue('archived')
    await expect(gallerySelect.locator('[data-ui-select-value]')).toHaveText('Archived')
    await expect(gallerySelect.locator('[data-ui-select-native]')).toHaveAttribute('data-input-events', '1')
    await expect(gallerySelect.locator('[data-ui-select-native]')).toHaveAttribute('data-change-events', '1')
    await gallerySelect.locator('[data-ui-select-trigger]').press('ArrowDown')
    await expect(gallerySelect.locator('[data-ui-select-menu]')).toBeVisible()
    await gallerySelect.getByRole('option', { name: 'Archived', exact: true }).press('ArrowUp')
    await gallerySelect.getByRole('option', { name: 'Active', exact: true }).press('Enter')
    await expect(gallerySelect.locator('[data-ui-select-native]')).toHaveValue('active')
    await expect(gallerySelect.locator('[data-ui-select-value]')).toHaveText('Active')
    await expect(gallerySelect.locator('[data-ui-select-native]')).toHaveAttribute('data-input-events', '2')
    await expect(gallerySelect.locator('[data-ui-select-native]')).toHaveAttribute('data-change-events', '2')
    await page.locator('[data-ui-checkbox-list="gallery-market-checkboxes"] label').last().click()
    await expect(page.locator('#gallery-market-checkboxes-2')).toBeChecked()
    await page.locator('[data-ui-checkbox-dropdown="gallery-market-dropdown"] summary').click()
    await expect(page.locator('[data-ui-checkbox-dropdown="gallery-market-dropdown"] details')).toHaveAttribute('open', '')
    await page.locator('#gallery-market-dropdown-2').check()
    await expect(page.locator('[data-ui-checkbox-dropdown-count]')).toHaveText('3')
    await expect(page.locator('[data-ui-checkbox-dropdown="gallery-market-dropdown"]')).toHaveJSProperty('tagName', 'DIV')
    expect(await page.locator('[data-admin-form-state]').evaluate((form) => new FormData(form).getAll('market_dropdown[]'))).toEqual(['de', 'at', 'ch'])
    await page.locator('#gallery-market-scroll-4').check()
    await expect(page.locator('#gallery-market-scroll-4')).toBeChecked()
    await page.locator('[data-ui-scrollable-checkbox-list="gallery-market-scroll"] input').evaluateAll((inputs) => {
        inputs.forEach((input) => {
            input.dataset.uiCheckboxGroupRequired = ''
            input.required = true
        })
    })
    for (const id of ['#gallery-market-scroll-0', '#gallery-market-scroll-2', '#gallery-market-scroll-4']) {
        await page.locator(id).uncheck()
    }
    expect(await page.locator('#gallery-market-scroll-0').evaluate((input) => input.checkValidity())).toBe(false)
    await page.locator('#gallery-market-scroll-1').check()
    expect(await page.locator('[data-ui-scrollable-checkbox-list="gallery-market-scroll"] input').evaluateAll((inputs) => inputs.every((input) => ! input.required))).toBe(true)
    await page.locator('#gallery-publish-date-trigger').click()
    await expect(page.locator('#gallery-publish-date-panel')).toBeVisible()
    await page.locator('#gallery-publish-date-panel [data-ui-date-picker-day="2026-08-13"]').click()
    await expect(page.locator('#gallery-publish-date')).toHaveValue('2026-08-13')
    await expect(page.locator('#gallery-publish-date-panel [data-ui-date-picker-day="2026-08-09"]')).toBeDisabled()
    await page.locator('#gallery-publish-date-panel [data-ui-date-picker-done]').click()
    await page.locator('#gallery-publish-date').evaluate((input) => { input.value = '2026-02-31' })
    await page.locator('#gallery-publish-date-trigger').click()
    await expect(page.locator('#gallery-publish-date-panel [data-ui-date-picker-month]')).not.toHaveText('March 2026')
    await page.locator('#gallery-publish-date-panel [data-ui-date-picker-done]').click()
    await page.locator('#gallery-publish-at-trigger').click()
    await expect(page.locator('#gallery-publish-at-panel')).toBeVisible()
    await page.locator('#gallery-publish-at-time').fill('14:30')
    await expect(page.locator('#gallery-publish-at')).toHaveValue('2026-08-05T14:30')
    await expect(page.locator('#gallery-publish-at-panel [data-ui-date-picker-day="2026-08-01"]')).toBeDisabled()
    expect(await page.locator('[data-admin-form-state]').evaluate((form) => new FormData(form).get('publish_at'))).toBe('2026-08-05T14:30')
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
