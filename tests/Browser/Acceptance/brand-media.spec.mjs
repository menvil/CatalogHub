import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

const logoWorkflowBrandId = 24
const detailUrl = `/admin/central/brands/${logoWorkflowBrandId}`
const mediaUrl = `${detailUrl}/media`

test('CA-012 and CA-014 persist the complete Brand logo repair, replace, and remove journey', async ({ page }) => {
    test.slow()

    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(detailUrl)

    const missingIssue = page.locator('[data-quality-issue-code="brand_logo_missing"]')
    await expect(missingIssue).toBeVisible()
    await missingIssue.getByRole('link', { name: 'Manage logo' }).click()

    await expect(page).toHaveURL(new RegExp(`${mediaUrl}$`))
    await expect(page.locator('[data-screen-id="CA-014"]')).toBeVisible()
    await expect(page.getByText('No primary logo assigned')).toBeVisible()

    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-a.png')
    const preview = page.getByAltText('Zotac logo')
    await expect(preview).toBeVisible()
    await expect(page.locator('[data-screen-region="asset-details"]')).toContainText('brand-logo-a.png')
    await expect(page.getByText('Brand logo updated.', { exact: true })).toBeVisible()
    const firstSource = await preview.getAttribute('src')

    await page.goto(detailUrl)
    await expect(page.locator('[data-quality-issue-code="brand_logo_missing"]')).toHaveCount(0)
    await expect(page.locator('[data-quality-issue-code="brand_logo_unusable"]')).toHaveCount(0)
    await expect(page.getByAltText('Zotac logo')).toBeVisible()

    await page.getByRole('tab', { name: 'Media', exact: true }).click()
    await expect(preview).toBeVisible()
    await page.locator('#logo').setInputFiles({
        name: 'broken-replacement.png',
        mimeType: 'image/png',
        buffer: Buffer.from('not a decodable image'),
    })
    await expect(page.getByRole('alert')).toContainText('could not be decoded safely')
    await expect(preview).toBeVisible()
    await expect(preview).toHaveAttribute('src', firstSource ?? '')

    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-b.png')
    await expect(page.locator('[data-screen-region="asset-details"]')).toContainText('brand-logo-b.png')
    await expect(preview).not.toHaveAttribute('src', firstSource ?? '')

    const removeLogo = page.getByRole('button', { name: 'Remove logo from brand', exact: true })
    await removeLogo.click()
    const dialog = page.getByRole('dialog', { name: 'Remove logo from Zotac?' })
    await expect(dialog).toBeVisible()
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
    await expect(dialog).toBeHidden()
    await expect(preview).toBeVisible()

    await removeLogo.click()
    await dialog.getByRole('button', { name: 'Remove logo from brand', exact: true }).click()
    await expect(page.getByText('No primary logo assigned')).toBeVisible()
    await expect(page.getByText('Brand logo assignment removed.', { exact: true })).toBeVisible()

    await page.setViewportSize({ width: 390, height: 844 })
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)

    await page.goto(detailUrl)
    await expect(page.locator('[data-quality-issue-code="brand_logo_missing"]')).toBeVisible()
    assertNoPageErrors()
})

test('CA-014 keeps a bounded 24-card Shared Media picker responsive and current after assignment', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands/14014/media')
    await expect(page.locator('[data-brand-media-fixture="brand-media-v6"]')).toBeVisible()
    const picker = page.locator('[data-screen-region="shared-media-picker"]')
    await expect(picker).toBeVisible()
    await expect(picker.getByRole('searchbox', { name: 'Search shared media' })).toBeVisible()
    await expect(picker.locator('[data-media-asset-card]')).toHaveCount(24)
    await expect(picker.locator('[data-media-asset-card][aria-current="true"]')).toContainText('Current')

    for (const viewport of [{ width: 1440, height: 1000 }, { width: 1280, height: 900 }, { width: 1024, height: 900 }, { width: 768, height: 1024 }, { width: 390, height: 844 }]) {
        await page.setViewportSize(viewport)
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    }

    await page.setViewportSize({ width: 1280, height: 900 })
    const widePrimaryBox = await page.locator('[data-screen-region="primary-logo"]').boundingBox()
    const wideDetailsBox = await page.locator('[data-screen-region="asset-details"]').boundingBox()
    expect(Math.abs((wideDetailsBox?.y ?? 0) - (widePrimaryBox?.y ?? 0))).toBeLessThan(4)

    for (const viewport of [{ width: 1024, height: 900 }, { width: 768, height: 1024 }]) {
        await page.setViewportSize(viewport)
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        const primaryBox = await page.locator('[data-screen-region="primary-logo"]').boundingBox()
        const detailsBox = await page.locator('[data-screen-region="asset-details"]').boundingBox()
        expect(detailsBox?.y ?? 0).toBeGreaterThan((primaryBox?.y ?? 0) + (primaryBox?.height ?? 0))
    }

    const expectedColumns = new Map([[1440, 8], [1280, 8], [1024, 4], [768, 4], [390, 1]])
    for (const [width, columns] of expectedColumns) {
        await page.setViewportSize({ width, height: width >= 1024 ? 1000 : 1024 })
        const cards = picker.locator('[data-media-asset-card]')
        const first = await cards.nth(0).boundingBox()
        const lastInRow = await cards.nth(columns - 1).boundingBox()
        expect(Math.abs((lastInRow?.y ?? 0) - (first?.y ?? 0))).toBeLessThan(4)
        if (columns < 24) {
            const firstNextRow = await cards.nth(columns).boundingBox()
            expect(firstNextRow?.y ?? 0).toBeGreaterThan((first?.y ?? 0) + 4)
        }
    }

    await page.setViewportSize({ width: 390, height: 844 })
    const firstCard = await picker.locator('[data-media-asset-card]').nth(0).boundingBox()
    const secondCard = await picker.locator('[data-media-asset-card]').nth(1).boundingBox()
    expect(secondCard?.y ?? 0).toBeGreaterThan(firstCard?.y ?? 0)
    await page.setViewportSize({ width: 1280, height: 900 })

    await picker.getByRole('searchbox', { name: 'Search shared media' }).fill('wordmark')
    await picker.getByRole('button', { name: 'Search', exact: true }).click()
    await expect(picker.locator('[data-media-asset-card]')).toHaveCount(1)
    await picker.getByRole('button', { name: 'Use as logo' }).click()

    await expect(page).toHaveURL(/\/admin\/central\/brands\/14014\/media$/)
    await expect(page.locator('[data-screen-region="shared-media-picker"]')).toBeVisible()
    await expect(page.locator('[data-screen-region="asset-details"]')).toContainText('apple-wordmark-black.png')
    await expect(page.locator('[data-media-asset-card][aria-current="true"]')).toContainText('apple-wordmark-black.png')
    await expect(page.getByText('Existing media asset assigned as the Brand logo.', { exact: true })).toBeVisible()
    assertNoPageErrors()
})
