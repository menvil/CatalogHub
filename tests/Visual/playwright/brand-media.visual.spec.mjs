import { expect, test } from '@playwright/test'
import { observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

async function settle(page) {
    await page.evaluate(() => document.fonts.ready)
    await page.addStyleTag({ content: '*,*::before,*::after{animation-duration:0s!important;transition-duration:0s!important}html{scrollbar-width:none}::-webkit-scrollbar{display:none}' })
}

test('CA-014 empty desktop matches its current v1 reference', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', 'super-admin@demo.cataloghub.test')
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands/20/media')
    await expect(page.getByText('No logo has been assigned to this brand yet.')).toBeVisible()
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-014__empty__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    assertNoPageErrors()
})

test('CA-014 logo desktop and mobile match current v1 references', async ({ page }) => {
    const errors = []
    page.on('pageerror', (error) => errors.push(error.message))
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', 'super-admin@demo.cataloghub.test')
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands/20/media')
    await page.locator('#logo').setInputFiles('tests/Visual/baselines/ca-014__empty__1440x1000.png')
    await page.locator('form[action$="/media/logo"] button[type="submit"]').click()
    await expect(page.getByAltText('Samsung logo')).toBeVisible()
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-014__logo-ready__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    await page.setViewportSize({ width: 390, height: 844 })
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-014__logo-ready__390x844.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.065 })
    expect(errors).toEqual([])
})
