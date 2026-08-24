import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

const activeBrandId = 20
const draftBrandId = 24

test('CA-012 supports list, detail, edit, and detail navigation', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands?q=Samsung')
    await page.locator(`[data-row-id="${activeBrandId}"]`).getByRole('link', { name: 'View', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${activeBrandId}$`))
    await expect(page.locator('[data-screen-id="CA-012"]')).toContainText('Samsung')
    await expect(page.getByText('CA-012', { exact: true })).toHaveCount(0)
    await expect(page.locator('[data-screen-region="status-context"]')).toContainText('Active')
    await page.getByRole('link', { name: 'Edit Brand', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${activeBrandId}/edit$`))
    await expect(page.locator('[data-screen-id="CA-013"]')).toBeVisible()
    await expect(page.getByText('CA-013', { exact: true })).toHaveCount(0)
    await expect(page.locator('#brand-form')).toHaveAttribute('data-admin-form-leave-warning', 'false')
    await expect(page.getByLabel('Name')).toHaveValue('Samsung')
    await page.getByRole('link', { name: 'Cancel', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${activeBrandId}$`))
    await expect(page.locator('[data-screen-id="CA-012"]')).toBeVisible()
    assertNoPageErrors()
})

test('CA-012 completes the explicit lifecycle workflow and archive cancellation', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${draftBrandId}`)

    const status = page.locator('[data-screen-region="status-context"]')
    await expect(status).toContainText('Draft')

    await page.getByRole('button', { name: 'Activate Brand', exact: true }).click()
    const activateDialog = page.getByRole('dialog', { name: 'Activate Zotac?' })
    await expect(activateDialog).toBeVisible()
    await expect(activateDialog).toContainText('This brand will become available for normal catalog use.')
    await activateDialog.getByRole('button', { name: 'Activate Brand', exact: true }).click()
    await expect(page.getByText('Brand activated.', { exact: true })).toBeVisible()
    await expect(status).toContainText('Active')

    const archiveTrigger = page.getByRole('button', { name: 'Archive Brand', exact: true })
    await archiveTrigger.click()
    const archiveDialog = page.getByRole('dialog', { name: 'Archive Zotac?' })
    await expect(archiveDialog).toBeVisible()
    await archiveDialog.getByRole('button', { name: 'Cancel', exact: true }).click()
    await expect(archiveDialog).toBeHidden()
    await expect(archiveTrigger).toBeFocused()
    await expect(status).toContainText('Active')

    await archiveTrigger.click()
    await archiveDialog.getByRole('button', { name: 'Archive Brand', exact: true }).click()
    await expect(page.getByText('Brand archived.', { exact: true })).toBeVisible()
    await expect(status).toContainText('Archived')

    await page.getByRole('button', { name: 'Restore Brand', exact: true }).click()
    const restoreDialog = page.getByRole('dialog', { name: 'Restore Zotac?' })
    await expect(restoreDialog).toBeVisible()
    await expect(restoreDialog).toContainText('The brand will return to Draft and must be activated separately before normal use.')
    await restoreDialog.getByRole('button', { name: 'Restore Brand', exact: true }).click()
    await expect(page.getByText('Brand restored to Draft.', { exact: true })).toBeVisible()
    await expect(status).toContainText('Draft')
    assertNoPageErrors()
})

test('CA-012 remains usable and overflow-free at 390px', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await page.setViewportSize({ width: 390, height: 844 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${activeBrandId}`)

    await expect(page.locator('[data-screen-id="CA-012"]')).toContainText('Samsung')
    await expect(page.locator('[data-screen-region="status-context"]')).toContainText('Active')
    await expect(page.getByRole('link', { name: 'Edit Brand', exact: true })).toBeVisible()
    await expect(page.locator('[data-screen-region="general-information"]')).toBeVisible()
    await expect(page.locator('[data-screen-region="usage"]')).toContainText('3 canonical products reference this brand.')
    await expect(page.locator('[data-screen-region="record-metadata"]')).toBeVisible()
    await expect(page.locator('[data-screen-region="lifecycle"]')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Archive Brand', exact: true })).toBeVisible()
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-012 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)

    assertNoPageErrors()
})
