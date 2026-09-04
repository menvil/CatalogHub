import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const states = [
    { name: 'ca-011__default__1440x1000.png', width: 1440, height: 1000, maxDiffPixelRatio: 0.02 },
    { name: 'ca-011__default__1024x900.png', width: 1024, height: 900, maxDiffPixelRatio: 0.025 },
    { name: 'ca-011__default__768x1024.png', width: 768, height: 1024, maxDiffPixelRatio: 0.05 },
    { name: 'ca-011__default__390x844.png', width: 390, height: 844, maxDiffPixelRatio: 0.065 },
]

for (const state of states) {
    test(`CA-011 Brands List matches its ${state.width}px reference`, async ({ page }) => {
        const assertNoPageErrors = observePageErrors(page)

        await page.setViewportSize({ width: state.width, height: state.height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        await page.goto('/admin/central/brands')
        await expect(page.locator('[data-screen-id="CA-011"]')).toBeVisible()
        await expect(page.getByText('Samsung', { exact: true })).toBeVisible()
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

        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        await expect(page).toHaveScreenshot([state.name], {
            animations: 'disabled',
            maxDiffPixelRatio: state.maxDiffPixelRatio,
            scale: 'css',
        })
        assertNoPageErrors()
    })
}
