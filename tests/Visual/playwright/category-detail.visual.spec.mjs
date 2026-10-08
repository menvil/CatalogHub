import { expect, test } from '@playwright/test'
import { createHash } from 'node:crypto'
import { mkdirSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { foundationDemo, observePageErrors, resetBrowserFixture, signIn } from '../../Browser/Support/acceptance.mjs'

test.beforeAll(() => resetBrowserFixture('category-detail-v1'))
test.afterAll(() => resetBrowserFixture())

for (const [state, id, width, height] of [
    ['active', 195003, 1440, 1000], ['active', 195003, 1024, 900],
    ['active', 195003, 768, 1024], ['active', 195003, 390, 844],
    ['archived', 195005, 1440, 1000], ['empty', 195004, 1440, 1000],
]) {
    test(`CA-017 ${state} ${width}px candidate evidence awaiting Product Owner review`, async ({ page }, testInfo) => {
        const noErrors = observePageErrors(page)
        await page.setViewportSize({ width, height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        await page.goto(`/admin/central/categories/${id}`)
        await expect(page.locator('[data-screen-id="CA-017"][data-fixture-version="category-detail-v1"]')).toBeVisible()
        await page.evaluate(() => document.fonts.ready)
        await page.addStyleTag({ content: `*, *::before, *::after { animation-duration: 0s !important; transition-duration: 0s !important; caret-color: transparent !important; } html { scrollbar-width: none !important; } ::-webkit-scrollbar { display: none !important; }` })
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        await page.evaluate(() => window.scrollTo(0, 0))
        await captureCandidate(page, testInfo, state, width, height)
        if (width === 390) {
            await page.locator('[data-screen-region="recent-activity"]').scrollIntoViewIfNeeded()
            await captureCandidate(page, testInfo, 'activity', width, height)
        }
        noErrors()
    })
}

async function captureCandidate(page, testInfo, state, width, height) {
    const directory = resolve('storage/logs/visual-artifacts/ca-017-candidates')
    mkdirSync(directory, { recursive: true })
    const path = resolve(directory, `ca-017__${state}__${width}x${height}.png`)
    const bytes = await page.screenshot({ path, animations: 'disabled', scale: 'css' })
    const checksum = createHash('sha256').update(bytes).digest('hex')
    writeFileSync(`${path}.sha256`, `${checksum}\n`)
    writeFileSync(`${path}.json`, JSON.stringify({ screen_id: 'CA-017', fixture: 'category-detail-v1', state, viewport: `${width}x${height}`, sha256: checksum, approval: 'pending-product-owner-review', source: 'categories-schema-prototype-v1', source_sha256: 'afa4cd4539d19818effb5796ef4e6b1da23c0c41dcd36fe144d6e92ae51a20de' }, null, 2) + '\n')
    await testInfo.attach(`CA-017 ${state} ${width}px pending review`, { path, contentType: 'image/png' })
}
