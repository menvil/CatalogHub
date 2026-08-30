import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'
import {
    activateRtlBrandTranslationLocale,
    restoreDefaultBrandTranslationLocales,
} from '../../Browser/Support/brand-translation-fixture.mjs'

const workspaceUrl = '/admin/central/brands/24/translations'

test.afterEach(() => {
    restoreDefaultBrandTranslationLocales()
})

async function settle(page) {
    await page.evaluate(() => document.fonts.ready)
    await page.addStyleTag({ content: '*,*::before,*::after{animation-duration:0s!important;caret-color:transparent!important;transition-duration:0s!important}html{scrollbar-width:none}::-webkit-scrollbar{display:none}' })
}

test('CA-015 v2 missing, approved, outdated, and mobile states match approved references', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`${workspaceUrl}/de-DE`)
    await expect(page.locator('[data-screen-id="CA-015"]')).toBeVisible()
    await expect(page.getByText('No translation row exists for this active locale. Nothing is persisted until Save.').first()).toBeVisible()
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-015__missing__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })

    await page.goto(`${workspaceUrl}/en-US`)
    await expect(page.getByText('Approved by')).toBeVisible()
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-015__approved__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })

    await page.goto(`${workspaceUrl}/fr-FR`)
    await expect(page.getByText('Marked outdated', { exact: true })).toBeVisible()
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-015__outdated__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })

    await page.setViewportSize({ width: 390, height: 844 })
    await page.goto(`${workspaceUrl}/en-US`)
    await settle(page)
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    await expect(page).toHaveScreenshot(['ca-015__approved__390x844.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.065 })

    activateRtlBrandTranslationLocale()
    await page.goto(`${workspaceUrl}/ar-SA`)
    await expect(page.getByLabel('Localized name')).toHaveAttribute('dir', 'rtl')
    await expect(page.locator('[data-admin-layout="central"]')).not.toHaveAttribute('dir', 'rtl')
    assertNoPageErrors()
})
