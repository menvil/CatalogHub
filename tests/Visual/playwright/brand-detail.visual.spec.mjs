import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const states = [
    { state: 'active', name: 'ca-012__active__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/20', maxDiffPixelRatio: 0.02 },
    { state: 'active', name: 'ca-012__active__390x844.png', width: 390, height: 844, url: '/admin/central/brands/20', maxDiffPixelRatio: 0.065 },
    { state: 'archived', name: 'ca-012__archived__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/21', maxDiffPixelRatio: 0.02 },
]

for (const state of states) {
    test(`CA-012 ${state.state} matches its ${state.width}px reference`, async ({ page }) => {
        const assertNoPageErrors = observePageErrors(page)

        await page.setViewportSize({ width: state.width, height: state.height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        await page.goto(state.url)
        await expect(page.locator('[data-brand-detail-fixture="brand-detail-v2"]')).toBeVisible()
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
