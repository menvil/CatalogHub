import { expect, test } from '@playwright/test'
import { existsSync } from 'node:fs'
import { resolve } from 'node:path'

test('Central login matches the approved deterministic reference', async ({ page }) => {
    const baseline = resolve('tests/Visual/baselines/z-001__default__1280x900.png')
    expect(existsSync(baseline), 'Approved visual baseline must exist before capture.').toBe(true)

    await page.goto('/admin/central/login', { waitUntil: 'networkidle' })
    await expect(page.locator('[data-auth-screen="central-login"]')).toBeVisible()
    await page.evaluate(() => document.fonts.ready)
    await page.addStyleTag({
        content: `
            *, *::before, *::after {
                animation-duration: 0s !important;
                caret-color: transparent !important;
                transition-duration: 0s !important;
            }
            html { scrollbar-width: none !important; }
            ::-webkit-scrollbar { display: none !important; }
        `,
    })

    // Allow only bounded font-rasterization drift (~0.22% of the fixed viewport).
    await expect(page).toHaveScreenshot(['z-001__default__1280x900.png'], {
        animations: 'disabled',
        maxDiffPixels: 2_500,
        scale: 'css',
    })
})
