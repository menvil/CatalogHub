import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const states = [
    { mode: 'create', name: 'ca-013__create__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/create', maxDiffPixelRatio: 0.02 },
    { mode: 'create', name: 'ca-013__create__390x844.png', width: 390, height: 844, url: '/admin/central/brands/create', maxDiffPixelRatio: 0.065 },
    { mode: 'edit', name: 'ca-013__edit__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/13013/edit', maxDiffPixelRatio: 0.02 },
    { mode: 'edit', name: 'ca-013__edit__390x844.png', width: 390, height: 844, url: '/admin/central/brands/13013/edit', maxDiffPixelRatio: 0.065 },
]

for (const state of states) {
    test(`CA-013 ${state.mode} matches its ${state.width}px reference`, async ({ page }) => {
        const assertNoPageErrors = observePageErrors(page)

        await page.setViewportSize({ width: state.width, height: state.height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        await page.goto(state.url)
        await expect(page.locator(`[data-brand-form-mode="${state.mode}"]`)).toBeVisible()
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
