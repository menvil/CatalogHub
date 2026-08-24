import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

const samsungBrandId = 20

test('CA-014 uploads, replaces, and safely removes the primary Brand logo', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${samsungBrandId}`)
    await page.getByRole('tab', { name: 'Media', exact: true }).click()

    await expect(page.locator('[data-screen-id="CA-014"]')).toBeVisible()
    await expect(page.getByText('No logo has been assigned to this brand yet.')).toBeVisible()

    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-a.png')
    await page.locator('form[action$="/media/logo"] button[type="submit"]').click()
    const preview = page.getByAltText('Samsung logo')
    await expect(preview).toBeVisible()
    await expect(page.getByText('brand-logo-a.png', { exact: true })).toBeVisible()
    const firstSource = await preview.getAttribute('src')

    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-b.png')
    await page.locator('form[action$="/media/logo"] button[type="submit"]').click()
    await expect(page.getByText('brand-logo-b.png', { exact: true })).toBeVisible()
    await expect(preview).not.toHaveAttribute('src', firstSource ?? '')

    await page.getByRole('button', { name: 'Remove logo', exact: true }).click()
    const dialog = page.getByRole('dialog', { name: 'Remove this logo from Samsung?' })
    await expect(dialog).toBeVisible()
    await dialog.getByRole('button', { name: 'Cancel', exact: true }).click()
    await expect(dialog).toBeHidden()
    await expect(preview).toBeVisible()

    await page.getByRole('button', { name: 'Remove logo', exact: true }).click()
    await dialog.getByRole('button', { name: 'Remove logo', exact: true }).click()
    await expect(page.getByText('No logo has been assigned to this brand yet.')).toBeVisible()
    assertNoPageErrors()
})
