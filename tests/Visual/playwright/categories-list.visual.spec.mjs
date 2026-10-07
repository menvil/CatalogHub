import { expect, test } from '@playwright/test'
import { createHash } from 'node:crypto'
import { existsSync, mkdirSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'

// Capture candidates for Product Owner review. Only compare a baseline once a
// reviewer promotes it through docs/ui/visual-diff-policy.md.
for (const [width, height] of [[1440, 1000], [1024, 900], [768, 1024], [390, 844]]) {
    test(`CA-016 Categories List ${width}px implementation evidence`, async ({ page }, testInfo) => {
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
        await captureEvidence(page, testInfo, width, height)
        if (width === 390) {
            await page.locator('tr[data-row-id="194005"]').scrollIntoViewIfNeeded()
            await captureEvidence(page, testInfo, width, height, 'cards')
        }
        noErrors()
    })
}

async function captureEvidence(page, testInfo, width, height, state = 'default') {
    const name = `ca-016__${state}__${width}x${height}.png`
    const baseline = resolve('tests/Visual/baselines', name)
    if (existsSync(baseline)) {
        await expect(page).toHaveScreenshot([name], { animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.02 })
    } else {
        const directory = resolve('storage/logs/visual-artifacts/ca-016-candidates')
        mkdirSync(directory, { recursive: true })
        const path = resolve(directory, name)
        const bytes = await page.screenshot({ path, animations: 'disabled', scale: 'css' })
        const checksum = createHash('sha256').update(bytes).digest('hex')
        writeFileSync(`${path}.sha256`, `${checksum}\n`)
        writeFileSync(`${path}.json`, JSON.stringify({
            screen_id: 'CA-016', state, fixture: 'categories-list-v1', viewport: `${width}x${height}`,
            sha256: checksum, approval: 'pending-product-owner-review',
            source: 'categories-schema-prototype-v1',
            source_sha256: 'c8e776138aa1356369fa2a48efb89f32540ac2d4234e4ee74a1406cba92094f2',
        }, null, 2) + '\n')
        await testInfo.attach(`CA-016 candidate ${width}px (pending review)`, { path, contentType: 'image/png' })
    }
}
