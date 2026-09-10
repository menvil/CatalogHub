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

test('CA-014 empty desktop matches its final convergence reference', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', 'super-admin@demo.cataloghub.test')
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands/24/media')
    await expect(page.getByText('No primary logo assigned')).toBeVisible()
    await settle(page)
    await assertNoHorizontalOverflow(page)
    await expect(page).toHaveScreenshot(['ca-014__empty__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    assertNoPageErrors()
})

test('CA-014 populated desktop, tablet and mobile match deterministic final references', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', 'super-admin@demo.cataloghub.test')
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${populatedBrandId}/media`)
    await expect(page.locator('[data-brand-media-fixture="brand-media-v4"]')).toBeVisible()
    await expect(page.getByAltText('Zyxel Apple Fixture logo')).toBeVisible()
    await expect(page.locator('[data-logo-variant]')).toHaveCount(3)
    await settle(page)
    await assertNoHorizontalOverflow(page)
    const previewBox = await page.locator('[data-logo-preview]').boundingBox()
    const detailsBox = await page.locator('[data-screen-region="asset-details"]').boundingBox()
    const variantsBox = await page.locator('[data-screen-region="generated-variants"]').boundingBox()
    expect(previewBox?.height ?? 0).toBeGreaterThanOrEqual(240)
    expect(previewBox?.height ?? 0).toBeLessThanOrEqual(320)
    expect(Math.abs((detailsBox?.y ?? 0) - (previewBox?.y ?? 0))).toBeLessThan(4)
    expect((variantsBox?.y ?? 0) + (variantsBox?.height ?? 0)).toBeLessThanOrEqual(1000)
    await expect(page).toHaveScreenshot(['ca-014__logo-ready__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    await page.setViewportSize({ width: 768, height: 1024 })
    await settle(page)
    await assertNoHorizontalOverflow(page)
    await expect(page).toHaveScreenshot(['ca-014__logo-ready__768x1024.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.04 })
    await page.setViewportSize({ width: 390, height: 844 })
    await settle(page)
    await assertNoHorizontalOverflow(page)
    await expect(page).toHaveScreenshot(['ca-014__logo-ready__390x844.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.065 })
    assertNoPageErrors()
})

test('CA-014 Shared Media picker matches its bounded desktop reference', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', 'super-admin@demo.cataloghub.test')
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${populatedBrandId}/media?picker=1`)
    await expect(page.getByRole('dialog', { name: 'Choose from Shared Media' })).toBeVisible()
    await expect(page.locator('[data-media-asset-card]')).toHaveCount(6)
    await expect(page.locator('[data-media-asset-card][aria-current="true"]')).toBeVisible()
    await settle(page)
    await assertNoHorizontalOverflow(page)
    await expect(page).toHaveScreenshot(['ca-014__picker-open__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    assertNoPageErrors()
})
