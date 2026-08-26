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
    await expect(page.locator('[data-screen-region="general-information"]')).toContainText('South Korea (KR)')
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

test('CA-012 manages normalized Brand tags and shows direct current category coverage', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    let nativeDialogs = 0
    page.on('dialog', async (dialog) => {
        nativeDialogs += 1
        await dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${activeBrandId}#classification`)

    const classification = page.locator('[data-screen-region="classification"]')
    await expect(classification).toContainText('No tags have been assigned to this Brand.')
    await expect(classification).toContainText('Smartphones')
    await expect(classification).toContainText('24 products')
    await expect(classification).toContainText('Televisions')
    await expect(classification).toContainText('12 products')
    await expect(classification).toContainText('Tablets')
    await expect(classification).toContainText('6 products')
    await expect(classification).not.toContainText('Laptops')

    await classification.getByRole('button', { name: 'Manage tags' }).click()
    const dialog = page.getByRole('dialog', { name: 'Manage tags' })
    const input = dialog.getByLabel('Brand tags')
    await input.fill('Consumer Electronics')
    await input.press('Enter')
    await input.fill('Premium')
    await input.press('Enter')
    await dialog.getByRole('button', { name: 'Save tags' }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${activeBrandId}#classification$`))
    await expect(classification.locator('[data-brand-tags]')).toContainText('Consumer Electronics')
    await expect(classification.locator('[data-brand-tags]')).toContainText('Premium')

    await classification.getByRole('button', { name: 'Manage tags' }).click()
    await input.fill('Temporary')
    await input.press('Enter')
    await expect(dialog.getByRole('button', { name: 'Remove Temporary' })).toHaveCount(1)
    await input.fill('unfinished tag')
    await dialog.getByRole('button', { name: 'Cancel' }).click()

    await classification.getByRole('button', { name: 'Manage tags' }).click()
    await expect(dialog.getByRole('button', { name: 'Remove Temporary' })).toHaveCount(0)
    await expect(dialog.getByRole('button', { name: 'Remove Premium' })).toHaveCount(1)
    await expect(input).toHaveValue('')
    await dialog.getByRole('button', { name: 'Remove Premium' }).focus()
    await dialog.getByRole('button', { name: 'Remove Premium' }).press('Enter')
    await expect(dialog.getByRole('button', { name: 'Remove Premium' })).toHaveCount(0)
    await page.keyboard.press('Escape')
    await expect(dialog).toBeHidden()

    await classification.getByRole('button', { name: 'Manage tags' }).click()
    await expect(dialog.getByRole('button', { name: 'Remove Premium' })).toHaveCount(1)
    await input.fill('premium')
    await input.press('Enter')
    await expect(dialog).toContainText('That tag is already added.')
    await expect(dialog.getByRole('button', { name: 'Remove Premium' })).toHaveCount(1)
    await dialog.getByRole('button', { name: 'Cancel' }).click()

    await classification.getByRole('button', { name: 'Manage tags' }).click()
    await expect(dialog.locator('[data-ui-tag-input-error]')).toBeHidden()
    await expect(input).toHaveValue('')
    const removePremium = dialog.getByRole('button', { name: 'Remove Premium' })
    await removePremium.focus()
    await removePremium.press('Enter')
    await expect(removePremium).toHaveCount(0)
    await dialog.getByRole('button', { name: 'Save tags' }).click()

    await expect(classification.locator('[data-brand-tags]')).toContainText('Consumer Electronics')
    await expect(classification.locator('[data-brand-tags]')).not.toContainText('Premium')
    expect(nativeDialogs).toBe(0)
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
    await expect(page.locator('[data-screen-region="usage"]')).toContainText('43 canonical products reference this brand.')
    await expect(page.locator('[data-screen-region="classification"]')).toBeVisible()
    await page.locator('[data-screen-region="classification"]').getByRole('button', { name: 'Manage tags' }).click()
    const tagDialog = page.getByRole('dialog', { name: 'Manage tags' })
    await expect(tagDialog).toBeVisible()
    await expect.poll(() => tagDialog.evaluate((element) => {
        const rect = element.getBoundingClientRect()
        return rect.left >= 0 && rect.right <= window.innerWidth && rect.top >= 0 && rect.bottom <= window.innerHeight
    })).toBe(true)
    await tagDialog.getByRole('button', { name: 'Cancel' }).click()
    await expect(page.locator('[data-screen-region="record-metadata"]')).toBeVisible()
    await expect(page.locator('[data-screen-region="lifecycle"]')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Archive Brand', exact: true })).toBeVisible()
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-012 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)

    assertNoPageErrors()
})
