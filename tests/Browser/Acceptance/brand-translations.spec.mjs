import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn, resetBrowserFixture } from '../Support/acceptance.mjs'
import {
    activateRtlBrandTranslationLocale,
    clearSourceTagline,
    workspacePersistence,
} from '../Support/brand-translation-fixture.mjs'

const workspaceBrandId = 24

test.beforeEach(() => resetBrowserFixture())

test.afterEach(() => resetBrowserFixture())

test('CA-012 and CA-015 complete the persisted Brand translation review workflow', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const dialogs = []
    page.on('dialog', async (dialog) => {
        dialogs.push(dialog.type())
        await dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${workspaceBrandId}`)

    const quality = page.locator('[data-screen-region="quality-completeness"]')
    const issues = page.locator('[data-screen-region="quality-issues"]')
    await expect(quality.locator('[data-screen-region="translation-summary"]')).toContainText('2 of 4 active locales complete')
    await expect(issues).toContainText('German (de-DE) translation is outdated')
    await issues.locator(`a[href$="/brands/${workspaceBrandId}/translations/de-DE"]`).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${workspaceBrandId}/translations/de-DE$`))
    await expect(page.locator('[data-screen-id="CA-015"]')).toBeVisible()
    await expect(page.getByText('Marked outdated. Review and save before approval.').first()).toBeVisible()
    await expect(page.getByLabel('Localized name', { exact: true })).toHaveValue('Zotac')
    await expect(page.locator('[data-source-field=tagline]')).toContainText('Innovation beyond the expected.')
    await page.getByRole('button', { name: 'Copy source for Localized name', exact: true }).click()
    await expect(page.getByLabel('Localized name', { exact: true })).toHaveValue('Zotac')
    await page.getByLabel('Tagline', { exact: true }).fill('Technologie für jeden')
    await page.getByLabel('Short description', { exact: true }).fill('Technologie und Elektronik für den Alltag.')
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

    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/de-DE`)
    await page.getByRole('button', { name: 'Mark outdated', exact: true }).click()
    await expect(page.getByText('Translation marked outdated.', { exact: true })).toBeVisible()
    await expect(page.getByLabel('Tagline', { exact: true })).toHaveValue('Technologie für jeden')
    await expect(page.getByText('Marked outdated', { exact: true }).first()).toBeVisible()

    await page.getByRole('tab', { name: 'Overview', exact: true }).click()
    await expect(issues).toContainText('German (de-DE) translation is outdated')

    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/de-DE`)
    await page.getByLabel('Tagline', { exact: true }).fill('Korrigierte Technologie für jeden')
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
    await expect(page.getByLabel('Localized name', { exact: true })).toHaveAttribute('dir', 'rtl')
    await expect(page.getByRole('textbox', { name: 'Description', exact: true })).toHaveAttribute('dir', 'rtl')
    await expect(page.locator('[data-source-field=description]')).toHaveAttribute('dir', 'ltr')
    await expect(page.locator('body')).not.toHaveAttribute('dir', 'rtl')
    await expect(page.locator('[data-admin-layout="central"]')).not.toHaveAttribute('dir', 'rtl')

    await page.setViewportSize({ width: 390, height: 844 })
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-015 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)
    await expect(page.getByRole('button', { name: 'Save translation', exact: true }).first()).toBeVisible()
    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/en-US?source=ar-SA`)
    await expect(page.locator('#name')).toHaveAttribute('dir', 'ltr')
    await expect(page.locator('[data-source-field=description]')).toHaveAttribute('dir', 'rtl')
    assertNoPageErrors()
})

test('CA-015 copies each field and all fields locally, protects existing text, and saves only on request', async ({ page }) => {
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/fr-FR?source=en-US`)
    const before = workspacePersistence()
    const mutations = []
    page.on('request', (request) => {
        if (request.method() !== 'GET') mutations.push(request.url())
    })
    const fields = ['name', 'tagline', 'short_description', 'description', 'seo_title', 'seo_description']
    for (const field of fields) {
        const source = await page.locator(`[data-source-field="${field}"]`).textContent()
        await page.locator(`[data-brand-translation-copy-target="${field}"]`).click()
        await expect(page.locator(`#${field}`)).toHaveValue(source)
        await page.locator(`#${field}`).fill('')
    }
    await page.getByRole('button', { name: 'Copy all from Source', exact: true }).click()
    for (const field of fields) {
        await expect(page.locator(`#${field}`)).toHaveValue(await page.locator(`[data-source-field="${field}"]`).textContent())
    }
    expect(mutations).toEqual([])
    expect(workspacePersistence()).toEqual(before)
    await page.reload()
    await expect(page.locator('#name')).toHaveValue('')
    await page.locator('#tagline').fill('Keep my draft')
    page.once('dialog', (dialog) => dialog.dismiss())
    await page.locator('[data-brand-translation-copy-target=tagline]').click()
    await expect(page.locator('#tagline')).toHaveValue('Keep my draft')
    page.once('dialog', (dialog) => dialog.accept())
    await page.locator('[data-brand-translation-copy-target=tagline]').click()
    await expect(page.locator('#tagline')).toHaveValue('Innovation beyond the expected.')
    await page.locator('#tagline').fill('Keep my draft')
    page.once('dialog', (dialog) => dialog.dismiss())
    await page.getByRole('button', { name: 'Copy all from Source', exact: true }).click()
    await expect(page.locator('#tagline')).toHaveValue('Keep my draft')
    page.once('dialog', async (dialog) => {
        expect(dialog.message()).toBe('Replace current target values with source values?')
        await dialog.accept()
    })
    await page.getByRole('button', { name: 'Copy all from Source', exact: true }).click()
    expect(workspacePersistence()).toEqual(before)
    await page.locator('#brand-translation-form').getByRole('button', { name: 'Save translation', exact: true }).click()
    await expect(page).toHaveURL(/translations\/fr-FR\?source=en-US$/)
    await expect(page.getByText('Translation saved.', { exact: true })).toBeVisible()
    const saved = workspacePersistence()
    expect(saved.brand).toEqual(before.brand)
    expect(saved.rows.find((row) => row.locale === 'en-US')).toEqual(before.rows.find((row) => row.locale === 'en-US'))
    expect(saved.rows.find((row) => row.locale === 'fr-FR').status).toBe('human_reviewed')
})

test('CA-015 preserves source choice through selection, navigation, and validation; skips empty source fields', async ({ page }) => {
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    clearSourceTagline()
    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/fr-FR?source=en-US`)
    await expect(page.locator('[data-source-field=tagline]')).toHaveText('No source value')
    await expect(page.locator('[data-brand-translation-copy-target=tagline]')).toHaveCount(0)
    await page.locator('#tagline').fill('Keep this field')
    await page.getByRole('button', { name: 'Copy all from Source', exact: true }).click()
    await expect(page.locator('#tagline')).toHaveValue('Keep this field')
    await page.locator('#name').fill('')
    await page.locator('#brand-translation-form').evaluate((form) => { form.noValidate = true })
    await page.locator('#brand-translation-form').getByRole('button', { name: 'Save translation', exact: true }).click()
    await expect(page).toHaveURL(/source=en-US$/)
    await expect(page.locator('#name')).toHaveAttribute('aria-invalid', 'true')
    await expect(page.locator('#name')).toHaveAttribute('aria-describedby', 'name-error')
    await page.locator('#source-language').selectOption('de-DE')
    await expect(page).toHaveURL(/source=de-DE$/)
    await expect(page.getByText('You are translating from an outdated source.')).toBeVisible()
    await page.getByRole('navigation', { name: 'Translation locales' }).locator('a[href*="/translations/en-US"]').click()
    await expect(page).toHaveURL(/translations\/en-US\?source=de-DE$/)
    await page.getByRole('navigation', { name: 'Translation locales' }).locator('a[href*="/translations/de-DE"]').click()
    await expect(page.locator('#source-language')).toHaveValue('en-US')
    await expect(page.locator('#source-language option[value="de-DE"]')).toHaveCount(0)
})
