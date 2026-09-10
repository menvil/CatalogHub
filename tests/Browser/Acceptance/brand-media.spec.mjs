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

    await page.getByRole('button', { name: 'Upload logo', exact: true }).click()
    await expect(page.getByRole('dialog', { name: 'Upload logo' })).toBeVisible()
    await expect(page.locator('#logo')).toBeFocused()
    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-a.png')
    await page.getByRole('dialog', { name: 'Upload logo' }).getByRole('button', { name: 'Upload logo', exact: true }).click()
    const preview = page.getByAltText('Zotac logo')
    await expect(preview).toBeVisible()
    await expect(page.getByRole('definition').filter({ hasText: 'brand-logo-a.png' })).toBeVisible()
    await expect(page.getByText('Brand logo updated.', { exact: true })).toBeVisible()
    const firstSource = await preview.getAttribute('src')

    await page.goto(detailUrl)
    await expect(page.locator('[data-quality-issue-code="brand_logo_missing"]')).toHaveCount(0)
    await expect(page.locator('[data-quality-issue-code="brand_logo_unusable"]')).toHaveCount(0)
    await expect(page.getByAltText('Zotac logo')).toBeVisible()

    await page.getByRole('tab', { name: 'Media', exact: true }).click()
    await expect(preview).toBeVisible()
    await page.getByRole('button', { name: 'Replace logo', exact: true }).click()
    await page.locator('#logo').setInputFiles({
        name: 'broken-replacement.png',
        mimeType: 'image/png',
        buffer: Buffer.from('not a decodable image'),
    })
    await page.getByRole('dialog', { name: 'Replace logo' }).getByRole('button', { name: 'Replace logo', exact: true }).click()
    await expect(page.getByRole('alert')).toContainText('could not be decoded safely')
    await expect(preview).toBeVisible()
    await expect(preview).toHaveAttribute('src', firstSource ?? '')

    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-b.png')
    await page.getByRole('dialog', { name: 'Replace logo' }).getByRole('button', { name: 'Replace logo', exact: true }).click()
    await expect(page.getByRole('definition').filter({ hasText: 'brand-logo-b.png' })).toBeVisible()
    await expect(preview).not.toHaveAttribute('src', firstSource ?? '')

    const moreLogoActions = page.locator('summary[aria-label="More logo actions"]')
    const removeLogo = page.getByRole('menuitem', { name: 'Remove logo from brand', exact: true })
    await moreLogoActions.click()
    await removeLogo.click()
    const dialog = page.getByRole('dialog', { name: 'Remove logo from Zotac?' })
    await expect(dialog).toBeVisible()
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
    await expect(dialog).toBeHidden()
    await expect(preview).toBeVisible()

    await moreLogoActions.click()
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

test('CA-014 loads the bounded Shared Media picker only on demand and closes it after assignment', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands/14014/media')
    await expect(page.locator('[data-brand-media-fixture="brand-media-v4"]')).toBeVisible()
    await expect(page.locator('[data-screen-region="shared-media-picker"]')).toHaveCount(0)

    for (const viewport of [{ width: 1280, height: 900 }, { width: 1024, height: 900 }]) {
        await page.setViewportSize(viewport)
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        const primaryBox = await page.locator('[data-screen-region="primary-logo"]').boundingBox()
        const detailsBox = await page.locator('[data-screen-region="asset-details"]').boundingBox()
        expect(Math.abs((detailsBox?.y ?? 0) - (primaryBox?.y ?? 0))).toBeLessThan(4)
    }
    await page.setViewportSize({ width: 768, height: 1024 })
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    const primaryBox = await page.locator('[data-screen-region="primary-logo"]').boundingBox()
    const detailsBox = await page.locator('[data-screen-region="asset-details"]').boundingBox()
    expect(detailsBox?.y ?? 0).toBeGreaterThan((primaryBox?.y ?? 0) + (primaryBox?.height ?? 0))
    await page.setViewportSize({ width: 1280, height: 900 })

    await page.getByRole('button', { name: 'Replace logo', exact: true }).click()
    const replaceDialog = page.getByRole('dialog', { name: 'Replace logo' })
    await expect(replaceDialog).toBeVisible()
    await replaceDialog.getByRole('button', { name: 'Cancel', exact: true }).click()
    await expect(replaceDialog).toBeHidden()

    await page.getByRole('link', { name: 'Choose from media' }).click()
    const picker = page.getByRole('dialog', { name: 'Choose from Shared Media' })
    await expect(picker).toBeVisible()
    await expect(picker.getByRole('searchbox', { name: 'Search shared media' })).toBeFocused()
    await expect(picker.locator('[data-media-asset-card]')).toHaveCount(6)
    await expect(picker.locator('[data-media-asset-card][aria-current="true"]')).toContainText('Current')

    await page.setViewportSize({ width: 390, height: 844 })
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
    const firstCard = await picker.locator('[data-media-asset-card]').nth(0).boundingBox()
    const secondCard = await picker.locator('[data-media-asset-card]').nth(1).boundingBox()
    expect(secondCard?.y ?? 0).toBeGreaterThan(firstCard?.y ?? 0)
    await page.setViewportSize({ width: 1280, height: 900 })

    await picker.getByRole('searchbox', { name: 'Search shared media' }).fill('wordmark')
    await picker.getByRole('button', { name: 'Search', exact: true }).click()
    await expect(picker.locator('[data-media-asset-card]')).toHaveCount(1)
    await picker.getByRole('button', { name: 'Use as logo' }).click()

    await expect(page).toHaveURL(/\/admin\/central\/brands\/14014\/media$/)
    await expect(page.locator('[data-screen-region="shared-media-picker"]')).toHaveCount(0)
    await expect(page.getByRole('definition').filter({ hasText: 'apple-wordmark-black.png' })).toBeVisible()
    await expect(page.getByText('Existing media asset assigned as the Brand logo.', { exact: true })).toBeVisible()
    assertNoPageErrors()
})
