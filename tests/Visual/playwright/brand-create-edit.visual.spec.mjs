import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const states = [
    { mode: 'create', state: 'create', name: 'ca-013__create__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/create', maxDiffPixelRatio: 0.02 },
    { mode: 'edit', state: 'edit', name: 'ca-013__edit__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/3/edit', maxDiffPixelRatio: 0.02 },
    { mode: 'edit', state: 'validation', name: 'ca-013__validation__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/3/edit', maxDiffPixelRatio: 0.02 },
    { mode: 'edit', state: 'edit', name: 'ca-013__edit__1024x1000.png', width: 1024, height: 1000, url: '/admin/central/brands/3/edit', maxDiffPixelRatio: 0.025 },
    { mode: 'edit', state: 'edit', name: 'ca-013__edit__768x1024.png', width: 768, height: 1024, url: '/admin/central/brands/3/edit', maxDiffPixelRatio: 0.035 },
    { mode: 'create', state: 'create', name: 'ca-013__create__390x844.png', width: 390, height: 844, url: '/admin/central/brands/create', maxDiffPixelRatio: 0.065 },
    { mode: 'edit', state: 'edit', name: 'ca-013__edit__390x844.png', width: 390, height: 844, url: '/admin/central/brands/3/edit', maxDiffPixelRatio: 0.065 },
    { mode: 'edit', state: 'ownership-picker', name: 'ca-013__ownership-picker__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/3/edit', maxDiffPixelRatio: 0.02 },
]

for (const state of states) {
    test(`CA-013 ${state.state} matches its ${state.width}px reference`, async ({ page }) => {
        const assertNoPageErrors = observePageErrors(page)

        await page.setViewportSize({ width: state.width, height: state.height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        await page.goto(state.url)
        await expect(page.locator(`[data-brand-form-mode="${state.mode}"]`)).toBeVisible()
        if (state.state === 'ownership-picker') {
            await page.locator('[data-screen-region="parent-company"]').getByRole('button', { name: 'Change', exact: true }).click()
            const dialog = page.getByRole('dialog', { name: 'Manage Parent Company' })
            const picker = dialog.getByRole('combobox', { name: 'Organization' })
            await picker.fill('Apple Operations')
            await expect(dialog.getByRole('option', { name: 'Apple Operations International — Organization #1301602', exact: true })).toBeVisible()
        } else if (state.state === 'validation') {
            await page.getByLabel('Website').fill('ftp://invalid.example.test')
            await page.getByRole('button', { name: 'Save changes', exact: true }).click()
            await expect(page.locator('#brand-website-error')).toBeVisible()
        }
        await page.evaluate(() => document.fonts.ready)
        await page.evaluate(() => window.scrollTo(0, 0))
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
