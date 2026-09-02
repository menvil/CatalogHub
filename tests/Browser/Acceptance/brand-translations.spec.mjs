import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../Support/acceptance.mjs'
import {
    activateRtlBrandTranslationLocale,
    restoreDefaultBrandTranslationLocales,
} from '../Support/brand-translation-fixture.mjs'

const samsungBrandId = 20
const workspaceBrandId = 24

test.afterEach(() => {
    restoreDefaultBrandTranslationLocales()
})

test('CA-012 and CA-015 complete the persisted Brand translation review workflow', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const dialogs = []
    page.on('dialog', async (dialog) => {
        dialogs.push(dialog.type())
        await dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${samsungBrandId}`)

    const quality = page.locator('[data-screen-region="quality-completeness"]')
    const issues = page.locator('[data-screen-region="quality-issues"]')
    await expect(quality.locator('[data-screen-region="translation-summary"]')).toContainText('0 of 4 active locales complete')
    await expect(issues).toContainText('German (de-DE) translation is missing')
    await issues.locator(`a[href$="/brands/${samsungBrandId}/translations/de-DE"]`).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${samsungBrandId}/translations/de-DE$`))
    await expect(page.locator('[data-screen-id="CA-015"]')).toBeVisible()
    await expect(page.getByText('No translation row exists for this active locale. Nothing is persisted until Save.').first()).toBeVisible()
    await expect(page.getByLabel('Localized name')).toHaveValue('')
    await expect(page.getByText('Canonical name', { exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Copy from Source', exact: true }).click()
    await expect(page.getByLabel('Localized name')).toHaveValue('Samsung')
    await page.getByLabel('Tagline').fill('Technologie für jeden')
    await page.getByLabel('Short description').fill('Technologie und Elektronik für den Alltag.')
    await page.locator('#status').selectOption('human_reviewed')
    await page.locator('#brand-translation-form').getByRole('button', { name: 'Save translation', exact: true }).click()

    await expect(page.getByText('Translation saved.', { exact: true })).toBeVisible()
    await expect(page.getByText('Translation created', { exact: true })).toBeVisible()
    await page.getByRole('button', { name: 'Approve translation', exact: true }).click()
    await expect(page.getByText('Translation approved.', { exact: true })).toBeVisible()
    await expect(page.getByText('Translation approved', { exact: true })).toBeVisible()
    await expect(page.getByLabel('Translation metadata and activity').getByText(foundationDemo.centralAdmin)).toBeVisible()

    await page.getByRole('tab', { name: 'Overview', exact: true }).click()
    await expect(issues).not.toContainText('German (de-DE) translation is missing')
    await expect(issues).not.toContainText('German (de-DE) translation is outdated')

    await page.goto(`/admin/central/brands/${samsungBrandId}/translations/de-DE`)
    await page.getByRole('button', { name: 'Mark outdated', exact: true }).click()
    await expect(page.getByText('Translation marked outdated.', { exact: true })).toBeVisible()
    await expect(page.getByLabel('Tagline')).toHaveValue('Technologie für jeden')
    await expect(page.getByText('Marked outdated', { exact: true })).toBeVisible()

    await page.getByRole('tab', { name: 'Overview', exact: true }).click()
    await expect(issues).toContainText('German (de-DE) translation is outdated')

    await page.goto(`/admin/central/brands/${samsungBrandId}/translations/de-DE`)
    await page.getByLabel('Tagline').fill('Korrigierte Technologie für jeden')
    await page.locator('#status').selectOption('human_reviewed')
    await page.locator('#brand-translation-form').getByRole('button', { name: 'Save translation', exact: true }).click()
    await page.getByRole('tab', { name: 'Overview', exact: true }).click()
    await expect(issues).not.toContainText('German (de-DE) translation is outdated')

    expect(dialogs).toEqual([])
    assertNoPageErrors()
})

test('CA-015 keeps the shell LTR, applies RTL only to target controls, and has no mobile overflow', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    activateRtlBrandTranslationLocale()
    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/ar-SA`)
    await expect(page.locator('[data-screen-id="CA-015"]')).toBeVisible()
    await expect(page.getByLabel('Localized name')).toHaveAttribute('dir', 'rtl')
    await expect(page.getByRole('textbox', { name: 'Description (optional)', exact: true })).toHaveAttribute('dir', 'rtl')
    await expect(page.locator('body')).not.toHaveAttribute('dir', 'rtl')
    await expect(page.locator('[data-admin-layout="central"]')).not.toHaveAttribute('dir', 'rtl')

    await page.setViewportSize({ width: 390, height: 844 })
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-015 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)
    await expect(page.getByRole('button', { name: 'Save translation', exact: true }).first()).toBeVisible()
    assertNoPageErrors()
})
