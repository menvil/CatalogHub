import { expect, test } from '@playwright/test'
import { observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const populatedBrandId = 14014

async function settle(page) {
    await page.evaluate(() => document.fonts.ready)
    await page.addStyleTag({ content: '*,*::before,*::after{animation-duration:0s!important;transition-duration:0s!important}html{scrollbar-width:none}::-webkit-scrollbar{display:none}' })
}

async function assertNoHorizontalOverflow(page) {
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    expect(await page.evaluate(() => window.scrollX)).toBe(0)
}

test('CA-014 empty desktop matches its v2 reference', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', 'super-admin@demo.cataloghub.test')
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands/20/media')
    await expect(page.getByText('No canonical logo assigned')).toBeVisible()
    await settle(page)
    await assertNoHorizontalOverflow(page)
    await expect(page).toHaveScreenshot(['ca-014__empty__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    assertNoPageErrors()
})

test('CA-014 populated desktop and mobile match deterministic v2 references', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', 'super-admin@demo.cataloghub.test')
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${populatedBrandId}/media`)
    await expect(page.locator('[data-brand-media-fixture="brand-media-v2"]')).toBeVisible()
    await expect(page.getByAltText('Zyxel Identity Fixture logo')).toBeVisible()
    await expect(page.locator('[data-logo-variant]')).toHaveCount(3)
    await settle(page)
    await assertNoHorizontalOverflow(page)
    await expect(page).toHaveScreenshot(['ca-014__logo-ready__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    await page.setViewportSize({ width: 390, height: 844 })
    await settle(page)
    await assertNoHorizontalOverflow(page)
    await expect(page).toHaveScreenshot(['ca-014__logo-ready__390x844.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.065 })
    assertNoPageErrors()
})
