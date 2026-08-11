import { expect, test } from '@playwright/test'
import { captureAcceptanceScreenshot, observePageErrors } from '../Support/acceptance.mjs'

const port = Number.parseInt(process.env.CATALOGHUB_BROWSER_PORT ?? '', 10)
const publicUrl = (host, path) => `http://${host}:${port}${path}`

test('Multi-category public fixture exposes isolated layout, locales, and canonical SEO', async ({ page }, testInfo) => {
    const assertNoPageErrors = observePageErrors(page)
    const response = await page.goto(publicUrl('www.tech-germany.test', '/de-DE'))

    expect(response?.status()).toBe(200)
    await expect(page.locator('[data-presentation-context="public-site"]')).toBeVisible()
    await expect(page.locator('[data-public-layout="multi-category"]')).toBeVisible()
    await expect(page.locator('[data-public-theme="cataloghub-multi"]')).toBeVisible()
    await expect(page.locator('[data-public-multi-shell-content]')).toBeVisible()
    await expect(page.locator('link[rel="canonical"]')).toHaveAttribute('href', 'https://tech-germany.test/de-DE')
    await expect(page.locator('link[rel="alternate"][hreflang="de-DE"]')).toHaveAttribute('href', 'https://tech-germany.test/de-DE')
    await expect(page.locator('link[rel="alternate"][hreflang="en-DE"]')).toHaveAttribute('href', 'https://tech-germany.test/en-DE')
    await expect(page.locator('[data-central-shell], [data-site-shell]')).toHaveCount(0)
    await captureAcceptanceScreenshot(page, testInfo, 'public-multi-desktop')
    assertNoPageErrors()
})

test('Single-category public fixture is reproducible at mobile width', async ({ page }, testInfo) => {
    const assertNoPageErrors = observePageErrors(page)
    await page.setViewportSize({ width: 360, height: 800 })
    const response = await page.goto(publicUrl('monitors-germany.test', '/en-DE'))

    expect(response?.status()).toBe(200)
    await expect(page.locator('[data-public-layout="single-category"]')).toBeVisible()
    await expect(page.locator('[data-public-theme="cataloghub-single"]')).toBeVisible()
    await expect(page.locator('[data-public-single-shell-content]')).toBeVisible()
    await expect(page.locator('[data-public-focused-hero]')).toContainText('Monitors Germany')
    await expect(page.locator('[data-public-locale-selector]')).toContainText('de-DE')
    await captureAcceptanceScreenshot(page, testInfo, 'public-single-mobile')
    assertNoPageErrors()
})

test('Archived and unknown public hosts fail closed without leaking another site', async ({ page }) => {
    for (const host of ['archived-germany.test', 'unknown.cataloghub.test']) {
        const response = await page.goto(publicUrl(host, '/de-DE'))

        expect(response?.status()).toBe(404)
        await expect(page.locator('[data-public-error="404"]')).toBeVisible()
        await expect(page.getByText('Tech Germany')).toHaveCount(0)
        await expect(page.getByText('Monitors Germany')).toHaveCount(0)
        await expect(page.locator('[data-central-shell], [data-site-shell]')).toHaveCount(0)
    }
})
