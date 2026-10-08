import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

for (const [width, height] of [[1440, 1000], [1024, 900], [768, 1024], [390, 844]]) {
    test(`CA-016 Categories List matches its ${width}px reference`, async ({ page }) => {
        const noErrors = observePageErrors(page)
        await page.setViewportSize({ width, height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        await page.goto('/admin/central/categories')
        await expect(page.locator('[data-screen-id="CA-016"][data-fixture-version="categories-list-v1"]')).toBeVisible()
        await expect(page.locator('[data-category-metric="total"] strong')).toHaveText('34')
        await page.evaluate(() => document.fonts.ready)
        await page.addStyleTag({ content: `
            *, *::before, *::after { animation-duration: 0s !important; transition-duration: 0s !important; caret-color: transparent !important; }
            html { scrollbar-width: none !important; }
            ::-webkit-scrollbar { display: none !important; }
        ` })
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        await compareReference(page, width, height)
        if (width === 390) {
            await page.locator('tr[data-row-id="194005"]').scrollIntoViewIfNeeded()
            await compareReference(page, width, height, 'cards')
        }
        noErrors()
    })
}

async function compareReference(page, width, height, state = 'default') {
    const name = `ca-016__${state}__${width}x${height}.png`
    await expect(page).toHaveScreenshot([name], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
}
