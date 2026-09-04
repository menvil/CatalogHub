import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

const states = [
    { mode: 'create', state: 'create', name: 'ca-013__create__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/create', maxDiffPixelRatio: 0.02 },
    { mode: 'create', state: 'create', name: 'ca-013__create__390x844.png', width: 390, height: 844, url: '/admin/central/brands/create', maxDiffPixelRatio: 0.065 },
    { mode: 'edit', state: 'edit', name: 'ca-013__edit__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/13013/edit', maxDiffPixelRatio: 0.02 },
    { mode: 'edit', state: 'edit', name: 'ca-013__edit__390x844.png', width: 390, height: 844, url: '/admin/central/brands/13013/edit', maxDiffPixelRatio: 0.065 },
    { mode: 'edit', state: 'ownership-populated', name: 'ca-013__ownership-populated__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/13013/edit', maxDiffPixelRatio: 0.02 },
    { mode: 'edit', state: 'ownership-populated', name: 'ca-013__ownership-populated__390x844.png', width: 390, height: 844, url: '/admin/central/brands/13013/edit', maxDiffPixelRatio: 0.065 },
    { mode: 'edit', state: 'ownership-picker', name: 'ca-013__ownership-picker__1440x1000.png', width: 1440, height: 1000, url: '/admin/central/brands/13013/edit', maxDiffPixelRatio: 0.02 },
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
            await page.locator('[data-screen-region="parent-company"]').getByRole('button', { name: 'Change Parent Company', exact: true }).click()
            const dialog = page.getByRole('dialog', { name: 'Manage Parent Company' })
            const picker = dialog.getByRole('combobox', { name: 'Organization' })
            await picker.fill('Samsung')
            await expect(dialog.getByRole('option', { name: 'Samsung Group International — Organization #1301602', exact: true })).toBeVisible()
        }
        await page.evaluate(() => document.fonts.ready)
        if (state.state === 'ownership-populated') {
            const ownershipTop = await page.locator('[data-screen-region="parent-company"]').evaluate(
                (element) => element.getBoundingClientRect().top,
            )
            await page.addStyleTag({ content: `body { transform: translateY(-${Math.max(0, ownershipTop - 120)}px); }` })
        } else {
            await page.evaluate(() => window.scrollTo(0, 0))
        }
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
