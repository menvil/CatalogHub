import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../Support/acceptance.mjs'

const samsungBrandId = 20

test('CA-015 saves independent locale translations without a native leave dialog', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const dialogs = []
    page.on('dialog', async (dialog) => {
        dialogs.push(dialog.type())
        await dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${samsungBrandId}`)
    await page.getByRole('tab', { name: 'Translations', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${samsungBrandId}/translations/en-US$`))
    await expect(page.locator('[data-screen-id="CA-015"]')).toBeVisible()
    await expect(page.getByRole('navigation', { name: 'Translation locales' })).toContainText('English (en-US)')
    await expect(page.getByRole('navigation', { name: 'Translation locales' })).toContainText('Deutsch (de-DE)')
    await expect(page.getByRole('navigation', { name: 'Translation locales' })).toContainText('Français (fr-FR)')

    await page.getByRole('link', { name: /Deutsch \(de-DE\)/ }).click()
    await expect(page.getByText('No translation has been created for this locale yet.')).toBeVisible()
    await expect(page.getByLabel('Localized name')).toHaveValue('Samsung')
    await page.getByLabel('Localized name').fill('Samsung')
    await page.getByLabel('Tagline').fill('Technologie für jeden')
    await page.getByLabel('Short description').fill('Technologie und Elektronik für den Alltag.')
    await page.locator('#status').selectOption('human_reviewed')
    await page.getByRole('button', { name: 'Save translation', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${samsungBrandId}/translations/de-DE$`))
    await expect(page.getByText('Translation saved.', { exact: true })).toBeVisible()
    await expect(page.getByLabel('Tagline')).toHaveValue('Technologie für jeden')
    expect(dialogs).toEqual([])

    await page.getByRole('link', { name: /Français \(fr-FR\)/ }).click()
    await expect(page.getByText('No translation has been created for this locale yet.')).toBeVisible()
    await expect(page.getByLabel('Localized name')).toHaveValue('Samsung')
    await expect(page.getByLabel('Tagline')).toHaveValue('')

    await page.getByRole('link', { name: /Deutsch \(de-DE\)/ }).click()
    await expect(page.getByLabel('Tagline')).toHaveValue('Technologie für jeden')
    await expect(page.getByLabel('Short description')).toHaveValue('Technologie und Elektronik für den Alltag.')

    await page.setViewportSize({ width: 390, height: 844 })
    await expect.poll(
        () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
        { message: 'CA-015 must not introduce horizontal page overflow at 390px.' },
    ).toBe(true)
    await expect(page.getByRole('button', { name: 'Save translation', exact: true })).toBeVisible()
    assertNoPageErrors()
})
