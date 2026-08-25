import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

const formFixtureId = 13013

test('CA-013 creates a normalized draft and returns to Brands', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const unexpectedDialogs = []
    page.on('dialog', (dialog) => {
        unexpectedDialogs.push(`${dialog.type()}: ${dialog.message()}`)
        void dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands')
    await page.getByRole('link', { name: 'Add Brand', exact: true }).click()

    await expect(page).toHaveURL(/\/admin\/central\/brands\/create$/)
    await expect(page.locator('[data-screen-id="CA-013"]')).toBeVisible()
    await expect(page.getByText('CA-013', { exact: true })).toHaveCount(0)
    await expect(page.locator('#brand-form')).toHaveAttribute('data-admin-form-leave-warning', 'false')
    await page.getByLabel('Name').fill('Samsung Electronics')
    await page.getByLabel('Website').fill('https://www.samsung.com')
    const country = page.getByRole('combobox', { name: 'Country' })
    await country.fill('Korea')
    await expect(page.getByRole('option', { name: 'South Korea (KR)', exact: true })).toBeVisible()
    await page.getByRole('option', { name: 'South Korea (KR)', exact: true }).click()
    await expect(country).toHaveValue('South Korea (KR)')
    await page.getByRole('button', { name: 'Create Brand', exact: true }).click()

    await expect(page).toHaveURL(/\/admin\/central\/brands$/)
    await expect(page.getByText('Brand created.', { exact: true })).toBeVisible()
    await page.goto('/admin/central/brands?q=Samsung+Electronics')
    const createdRow = page.getByRole('row').filter({ hasText: 'Samsung Electronics' })
    await createdRow.getByRole('link', { name: 'View', exact: true }).click()
    await expect(page.locator('[data-screen-region="general-information"]')).toContainText('South Korea (KR)')
    await expect(page.getByRole('dialog')).toHaveCount(0)
    expect(unexpectedDialogs, 'Create Brand must not trigger a native browser confirmation.').toEqual([])
    assertNoPageErrors()
})

test('CA-013 supports keyboard Country selection, Escape, change, and clear', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const unexpectedDialogs = []
    page.on('dialog', (dialog) => {
        unexpectedDialogs.push(`${dialog.type()}: ${dialog.message()}`)
        void dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${formFixtureId}/edit`)

    const country = page.getByRole('combobox', { name: 'Country' })
    await expect(country).toHaveValue('South Korea (KR)')
    await country.fill('Germany')
    await expect(page.getByRole('option', { name: 'Germany (DE)', exact: true })).toBeVisible()
    await country.press('Escape')
    await expect(country).toHaveValue('South Korea (KR)')
    await expect(page.locator('#brand-country-listbox')).toBeHidden()
    await country.focus()
    await expect(page.getByRole('option', { name: 'Afghanistan (AF)', exact: true })).toBeVisible()
    await country.fill('Germany')
    await page.getByRole('heading', { name: 'Edit Brand', exact: true }).click()
    await expect(page.locator('#brand-country-listbox')).toBeHidden()
    await country.focus()
    await expect(page.getByRole('option', { name: 'Afghanistan (AF)', exact: true })).toBeVisible()

    await country.fill('Japan')
    await country.press('ArrowDown')
    await expect(country).toHaveAttribute('aria-activedescendant', /brand-country-option-/)
    await country.press('Enter')
    await expect(country).toHaveValue('Japan (JP)')
    await page.getByRole('button', { name: 'Save changes', exact: true }).click()
    await expect(page.getByText('Brand updated.', { exact: true })).toBeVisible()
    await expect(country).toHaveValue('Japan (JP)')

    await page.getByRole('button', { name: 'Clear Country', exact: true }).click()
    await expect(country).toHaveValue('')
    await page.getByRole('button', { name: 'Save changes', exact: true }).click()
    await expect(page.getByText('Brand updated.', { exact: true })).toBeVisible()
    await expect(country).toHaveValue('')
    expect(unexpectedDialogs, 'Country selection must not trigger a native browser confirmation.').toEqual([])
    assertNoPageErrors()
})

test('CA-013 displays server validation, retains submitted input, and writes nothing partially', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${formFixtureId}/edit`)
    await page.getByLabel('Name').fill('Samsung Invalid Submission')
    await page.getByLabel('Website').fill('ftp://invalid.example.test')
    await page.getByRole('button', { name: 'Save changes', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${formFixtureId}/edit$`))
    await expect(page.getByLabel('Website')).toHaveAttribute('aria-invalid', 'true')
    await expect(page.locator('#brand-website-error')).toBeVisible()
    await expect(page.getByLabel('Name')).toHaveValue('Samsung Invalid Submission')
    await page.reload()
    await expect(page.getByLabel('Name')).toHaveValue('Samsung Form Fixture')
    await expect(page.getByLabel('Website')).toHaveValue('https://www.samsung.com')
    await expect(page.getByText('Draft', { exact: true })).toBeVisible()
    assertNoPageErrors()
})

test('CA-013 edits canonical fields while preserving slug and lifecycle', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const unexpectedDialogs = []
    page.on('dialog', (dialog) => {
        unexpectedDialogs.push(`${dialog.type()}: ${dialog.message()}`)
        void dialog.dismiss()
    })

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands?q=Samsung+Form+Fixture')
    await page.locator(`[data-row-id="${formFixtureId}"]`).getByRole('link', { name: 'Edit', exact: true }).click()
    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${formFixtureId}/edit$`))

    await page.getByLabel('Name').fill('Samsung Form Updated')
    await page.getByLabel('Website').fill('https://www.samsung.com/global')
    await page.getByRole('button', { name: 'Save changes', exact: true }).click()

    await expect(page.getByText('Brand updated.', { exact: true })).toBeVisible()
    await expect(page.getByLabel('Name')).toHaveValue('Samsung Form Updated')
    await expect(page.getByLabel('Slug')).toHaveValue('samsung-form-fixture')
    await expect(page.getByLabel('Website')).toHaveValue('https://www.samsung.com/global')
    await expect(page.getByText('Draft', { exact: true })).toBeVisible()
    expect(unexpectedDialogs, 'Save changes must not trigger a native browser confirmation.').toEqual([])
    assertNoPageErrors()
})

test('CA-013 create and edit remain usable without horizontal overflow at 390px', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await page.setViewportSize({ width: 390, height: 844 })
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()

    for (const url of ['/admin/central/brands/create', `/admin/central/brands/${formFixtureId}/edit`]) {
        await page.goto(url)
        await expect(page.locator('[data-screen-id="CA-013"]')).toBeVisible()
        await expect(page.getByLabel('Name')).toBeVisible()
        await expect(page.getByRole('combobox', { name: 'Country' })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Cancel', exact: true })).toBeVisible()
        await expect.poll(
            () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
            { message: `${url} must not introduce horizontal page overflow at 390px.` },
        ).toBe(true)
    }

    await page.setViewportSize({ width: 1920, height: 1080 })
    await page.goto(`/admin/central/brands/${formFixtureId}/edit`)
    const workspaceWidth = await page.locator('[data-admin-workspace]').evaluate((element) => element.getBoundingClientRect().width)
    const formCardWidth = await page.locator('[data-screen-region="general-form-card"]').evaluate((element) => element.getBoundingClientRect().width)
    expect(workspaceWidth).toBeGreaterThan(1500)
    expect(formCardWidth).toBeLessThanOrEqual(900)

    assertNoPageErrors()
})
