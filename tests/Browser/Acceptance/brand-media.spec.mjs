import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

const samsungBrandId = 20
const detailUrl = `/admin/central/brands/${samsungBrandId}`
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
    await expect(page.getByText('No canonical logo assigned')).toBeVisible()

    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-a.png')
    await page.locator('[data-logo-upload-form] button[type="submit"]').click()
    const preview = page.getByAltText('Samsung logo')
    await expect(preview).toBeVisible()
    await expect(page.getByRole('definition').filter({ hasText: 'brand-logo-a.png' })).toBeVisible()
    await expect(page.getByText('Brand logo updated.', { exact: true })).toBeVisible()
    const firstSource = await preview.getAttribute('src')

    await page.goto(detailUrl)
    await expect(page.locator('[data-quality-issue-code="brand_logo_missing"]')).toHaveCount(0)
    await expect(page.locator('[data-quality-issue-code="brand_logo_unusable"]')).toHaveCount(0)
    await expect(page.getByAltText('Samsung logo')).toBeVisible()

    await page.getByRole('tab', { name: 'Media', exact: true }).click()
    await expect(preview).toBeVisible()
    await page.locator('#logo').setInputFiles({
        name: 'broken-replacement.png',
        mimeType: 'image/png',
        buffer: Buffer.from('not a decodable image'),
    })
    await page.locator('[data-logo-upload-form] button[type="submit"]').click()
    await expect(page.getByRole('alert')).toContainText('could not be decoded safely')
    await expect(preview).toBeVisible()
    await expect(preview).toHaveAttribute('src', firstSource ?? '')

    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-b.png')
    await page.locator('[data-logo-upload-form] button[type="submit"]').click()
    await expect(page.getByRole('definition').filter({ hasText: 'brand-logo-b.png' })).toBeVisible()
    await expect(preview).not.toHaveAttribute('src', firstSource ?? '')

    await page.getByRole('button', { name: 'Remove assignment', exact: true }).click()
    const dialog = page.getByRole('dialog', { name: 'Remove the canonical logo from Samsung?' })
    await expect(dialog).toBeVisible()
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
    await expect(dialog).toBeHidden()
    await expect(preview).toBeVisible()

    await page.getByRole('button', { name: 'Remove assignment', exact: true }).click()
    await dialog.getByRole('button', { name: 'Remove assignment', exact: true }).click()
    await expect(page.getByText('No canonical logo assigned')).toBeVisible()
    await expect(page.getByText('Brand logo assignment removed.', { exact: true })).toBeVisible()

    await page.setViewportSize({ width: 390, height: 844 })
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)

    await page.goto(detailUrl)
    await expect(page.locator('[data-quality-issue-code="brand_logo_missing"]')).toBeVisible()
    assertNoPageErrors()
})
