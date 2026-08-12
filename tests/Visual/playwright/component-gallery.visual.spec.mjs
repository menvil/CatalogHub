import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const states = [
    { name: 'z-010__catalog__1440x1000.png', width: 1440, height: 1000 },
    { name: 'z-010__catalog__390x844.png', width: 390, height: 844 },
]

for (const state of states) {
    test(`Foundation Component Gallery matches ${state.width}px reference`, async ({ page }) => {
        const assertNoPageErrors = observePageErrors(page)

        await page.setViewportSize({ width: state.width, height: state.height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page).toHaveURL(/\/admin\/central(?:\/|$)/)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        await page.goto('/admin/central/component-gallery')
        await expect(page.locator('[data-admin-components-section="catalog"]')).toBeVisible()
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
            // A fixed viewport avoids OS-dependent full-page height drift; 2% covers font rasterization only.
            maxDiffPixelRatio: 0.02,
            scale: 'css',
        })
        assertNoPageErrors()
    })
}
