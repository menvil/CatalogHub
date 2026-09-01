import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

const activeBrandId = 20
const completeBrandId = 21
const draftBrandId = 24

test('CA-012 derives complete and needs-attention quality states from persisted Brand data', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()

    await page.goto(`/admin/central/brands/${completeBrandId}`)
    const completeQuality = page.locator('[data-screen-region="quality-completeness"]')
    const completeIssues = page.locator('[data-screen-region="quality-issues"]')
    await expect(completeQuality).toContainText('Complete')
    await expect(completeQuality).toContainText('100%')
    await expect(completeQuality).toContainText('10 of 10 checks complete')
    await expect(completeIssues).toContainText('All applicable quality checks are complete.')

    await page.goto(`/admin/central/brands/${activeBrandId}`)
    const quality = page.locator('[data-screen-region="quality-completeness"]')
    const issues = page.locator('[data-screen-region="quality-issues"]')
    await expect(quality).toContainText('Needs attention')
    await expect(quality).toContainText('50%')
    await expect(quality).toContainText('5 of 10 checks complete')
    await expect(quality.locator('[data-screen-region="translation-summary"]')).toContainText('0 of 4 active locales complete')
    await expect(issues).toContainText('Primary Brand logo is missing')
    const germanIssue = issues.locator('[data-quality-issue-code]').filter({ hasText: 'German (de-DE) translation is missing' })
    await expect(germanIssue).toBeVisible()
    await germanIssue.getByRole('link', { name: 'Edit translation', exact: true }).click()
    await expect(page.locator('[data-screen-id="CA-015"]')).toBeVisible()
    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${activeBrandId}/translations/de-DE$`))
    await page.getByRole('tab', { name: 'Overview', exact: true }).click()

    await issues.getByRole('link', { name: 'Manage logo', exact: true }).click()
    await expect(page.locator('[data-screen-id="CA-014"]')).toBeVisible()
    await page.locator('#logo').setInputFiles('tests/Fixtures/media/brand-logo-a.png')
    await page.locator('form[action$="/media/logo"] button[type="submit"]').click()
    await expect(page.getByAltText('Samsung logo')).toBeVisible()

    await page.getByRole('tab', { name: 'Overview', exact: true }).click()
    await expect(quality).toContainText('60%')
    await expect(quality).toContainText('6 of 10 checks complete')
    await expect(issues).not.toContainText('Primary Brand logo is missing')

    await page.getByRole('tab', { name: 'Media', exact: true }).click()
    await page.getByRole('button', { name: 'Remove assignment', exact: true }).click()
    await page.getByRole('dialog', { name: 'Remove the canonical logo from Samsung?' }).getByRole('button', { name: 'Remove assignment', exact: true }).click()
    await expect(page.getByText('No canonical logo assigned')).toBeVisible()
    assertNoPageErrors()
})

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
    await expect(page.locator('[data-parent-company]')).toHaveText('Samsung Electronics Co., Ltd.')
    await expect(page.locator('[data-screen-region="translation-summary"]')).toContainText('active locales complete')
    await expect(page.locator('[data-screen-region="usage"]')).toContainText('42')
    await expect(page.locator('[data-screen-region="usage"]')).toContainText('Smartphones')
    await expect(page.locator('[data-screen-region="external-identities"]')).toContainText('Manufacturer API')
    await page.getByRole('link', { name: 'Edit Brand', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${activeBrandId}/edit$`))
    await expect(page.locator('[data-screen-id="CA-013"]')).toBeVisible()
    await expect(page.getByText('CA-013', { exact: true })).toHaveCount(0)
    await expect(page.locator('#brand-form')).toHaveAttribute('data-admin-form-leave-warning', 'false')
    await expect(page.locator('#brand-name')).toHaveValue('Samsung')
    await expect(page.getByRole('textbox', { name: 'Name' })).toHaveValue('Samsung')
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
    const profileIssue = page.locator('[data-screen-region="quality-issues"] [data-quality-issue-code]').filter({ hasText: 'Website is missing' })
    await profileIssue.getByRole('link', { name: 'Edit profile', exact: true }).click()
    await expect(page.locator('[data-screen-id="CA-013"]')).toBeVisible()
    await page.getByRole('link', { name: 'Cancel', exact: true }).click()
    await expect(page.locator('[data-screen-id="CA-012"]')).toBeVisible()

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
    const usage = page.locator('[data-screen-region="usage"]')
    await expect(classification).toContainText('No tags have been assigned to this Brand.')
    await expect(usage).toContainText('Smartphones')
    await expect(usage).toContainText('24 products')
    await expect(usage).toContainText('Televisions')
    await expect(usage).toContainText('12 products')
    await expect(usage).toContainText('Tablets')
    await expect(usage).toContainText('6 products')
    await expect(usage).not.toContainText('Laptops')

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

test('CA-012 manages external identity provenance with reversible modal editing', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    let nativeDialogs = 0
    page.on('dialog', async (dialog) => {
        nativeDialogs += 1
        await dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${activeBrandId}#external-identities`)

    const provenance = page.locator('[data-screen-region="external-identities"]')
    await expect(provenance).toContainText('Manufacturer API')
    await expect(provenance).toContainText('brand-00142')
    await expect(provenance).toContainText('Open record')
    await expect(provenance).toContainText('Legacy Feed')
    await expect(provenance).toContainText('Inactive')
    await expect(provenance).toContainText('SAMSUNG')

    await provenance.getByRole('button', { name: 'Add identity' }).click()
    const addDialog = page.getByRole('dialog', { name: 'Add external identity' })
    await addDialog.getByLabel('Source').click()
    await addDialog.getByRole('option', { name: 'Manufacturer API (manufacturer_api)', exact: true }).click()
    await addDialog.locator('input[name="external_id"]').fill('temporary-cancelled-id')
    await addDialog.getByLabel('External record URL').fill('https://example.test/temporary')
    await addDialog.getByRole('button', { name: 'Cancel' }).click()

    await provenance.getByRole('button', { name: 'Add identity' }).click()
    await expect(addDialog.locator('#add-external-identity-source')).toHaveValue('')
    await expect(addDialog.locator('input[name="external_id"]')).toHaveValue('')
    await expect(addDialog.getByLabel('External record URL')).toHaveValue('')
    await addDialog.getByLabel('Source').click()
    await addDialog.getByRole('option', { name: 'Manufacturer API (manufacturer_api)', exact: true }).click()
    await addDialog.locator('input[name="external_id"]').fill('brand-browser-001')
    await addDialog.getByLabel('External record URL').fill('https://example.test/brands/brand-browser-001')
    await addDialog.getByRole('button', { name: 'Add identity' }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${activeBrandId}#external-identities$`))
    let browserRow = provenance.locator('[data-external-identity-id]').filter({ hasText: 'brand-browser-001' })
    await expect(browserRow).toHaveCount(1)
    await browserRow.getByRole('button', { name: 'Edit' }).click()
    const editDialog = page.getByRole('dialog', { name: 'Edit external identity' })
    await expect(editDialog).toContainText('Manufacturer API')
    await expect(editDialog.getByRole('combobox')).toHaveCount(0)
    await editDialog.locator('input[name="external_id"]').fill('temporary-escape-id')
    await page.keyboard.press('Escape')
    await expect(editDialog).toBeHidden()

    await browserRow.getByRole('button', { name: 'Edit' }).click()
    await expect(editDialog.locator('input[name="external_id"]')).toHaveValue('brand-browser-001')
    await editDialog.locator('input[name="external_id"]').fill('brand-browser-002')
    await editDialog.getByRole('button', { name: 'Save identity' }).click()
    browserRow = provenance.locator('[data-external-identity-id]').filter({ hasText: 'brand-browser-002' })
    await expect(browserRow).toHaveCount(1)

    await browserRow.getByRole('button', { name: 'Remove' }).click()
    const removeDialog = page.getByRole('dialog', { name: 'Remove external identity?' })
    await expect(removeDialog).toContainText('Manufacturer API')
    await expect(removeDialog).toContainText('brand-browser-002')
    await expect(removeDialog).toContainText('does not delete the ImportSource')
    await removeDialog.getByRole('button', { name: 'Remove identity' }).click()
    await expect(provenance).not.toContainText('brand-browser-002')
    await expect(provenance).toContainText('Manufacturer API')

    await provenance.getByRole('button', { name: 'Add identity' }).click()
    await addDialog.getByLabel('Source').click()
    await addDialog.getByRole('option', { name: 'Manufacturer API (manufacturer_api)', exact: true }).click()
    await addDialog.locator('input[name="external_id"]').fill('invalid-url-id')
    await addDialog.getByLabel('External record URL').fill('https://user:pass@example.test/brand')
    await addDialog.getByRole('button', { name: 'Add identity' }).click()
    await expect(addDialog).toBeVisible()
    await expect(addDialog).toContainText('without embedded credentials')
    await expect(addDialog.locator('input[name="external_id"]')).toHaveValue('invalid-url-id')
    await addDialog.getByRole('button', { name: 'Cancel' }).click()

    await provenance.getByRole('button', { name: 'Add identity' }).click()
    await expect(addDialog.locator('input[name="external_id"]')).toHaveValue('')
    await expect(addDialog.getByLabel('External record URL')).toHaveValue('')
    await expect(addDialog.getByText('without embedded credentials')).toHaveCount(0)
    await addDialog.getByRole('button', { name: 'Cancel' }).click()

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
    await expect(page.locator('[data-screen-region="usage"]')).toContainText('42 current canonical products reference this brand.')
    await expect(page.locator('[data-screen-region="classification"]')).toBeVisible()
    await page.locator('[data-screen-region="classification"]').getByRole('button', { name: 'Manage tags' }).click()
    const tagDialog = page.getByRole('dialog', { name: 'Manage tags' })
    await expect(tagDialog).toBeVisible()
    await expect.poll(() => tagDialog.evaluate((element) => {
        const rect = element.getBoundingClientRect()
        return rect.left >= 0 && rect.right <= window.innerWidth && rect.top >= 0 && rect.bottom <= window.innerHeight
    })).toBe(true)
    await tagDialog.getByRole('button', { name: 'Cancel' }).click()
    const provenance = page.locator('[data-screen-region="external-identities"]')
    await expect(provenance).toBeVisible()
    await expect(provenance).toContainText('brand-00142')
    await provenance.getByRole('button', { name: 'Add identity' }).click()
    const identityDialog = page.getByRole('dialog', { name: 'Add external identity' })
    await expect(identityDialog).toBeVisible()
    await expect.poll(() => identityDialog.evaluate((element) => {
        const rect = element.getBoundingClientRect()
        return rect.left >= 0 && rect.right <= window.innerWidth && rect.top >= 0 && rect.bottom <= window.innerHeight
    })).toBe(true)
    await identityDialog.getByRole('button', { name: 'Cancel' }).click()
    await expect(page.locator('[data-screen-region="record-metadata"]')).toBeVisible()
    await expect(page.locator('[data-screen-region="lifecycle"]')).toBeVisible()
    await expect(page.getByRole('button', { name: 'Archive Brand', exact: true })).toBeVisible()
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-012 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)

    assertNoPageErrors()
})
