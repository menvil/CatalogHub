import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const url = '/admin/central/brands/20/translations/de-DE'

async function settle(page) {
    await page.evaluate(() => document.fonts.ready)
    await page.addStyleTag({ content: '*,*::before,*::after{animation-duration:0s!important;caret-color:transparent!important;transition-duration:0s!important}html{scrollbar-width:none}::-webkit-scrollbar{display:none}' })
}

test('CA-015 missing and existing v1 states match current references', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 1440, height: 1000 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(url)
    await expect(page.locator('[data-screen-id="CA-015"]')).toBeVisible()
    await expect(page.getByText('No translation has been created for this locale yet.')).toBeVisible()
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-015__missing__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })

    await page.getByLabel('Localized name').fill('Samsung')
    await page.getByLabel('Tagline').fill('Technologie für jeden')
    await page.getByLabel('Short description').fill('Technologie und Elektronik für den Alltag.')
    await page.locator('#status').selectOption('human_reviewed')
    await page.getByRole('button', { name: 'Save translation', exact: true }).click()
    await expect(page.getByText('Translation saved.', { exact: true })).toBeVisible()
    await settle(page)
    await expect(page).toHaveScreenshot(['ca-015__existing__1440x1000.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })

    await page.setViewportSize({ width: 390, height: 844 })
    await settle(page)
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    await expect(page).toHaveScreenshot(['ca-015__existing__390x844.png'], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.065 })
    assertNoPageErrors()
})
