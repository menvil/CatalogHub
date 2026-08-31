import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

const formFixtureId = 13013
const ownershipFixtureId = 13016

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
    await page.getByRole('textbox', { name: 'Name' }).fill('Samsung Electronics')
    await page.getByLabel('Founded year').fill('1938')
    await page.getByLabel('Website').fill('https://www.samsung.com/')
    await page.getByLabel('Support URL').fill('https://www.samsung.com/support/')
    await page.getByLabel('Contact email').fill('support@example.com')
    await page.getByLabel('Primary color (optional)', { exact: true }).fill('#1428A0')
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
    await expect(page.locator('[data-screen-region="general-information"]')).toContainText('1938')
    await expect(page.locator('[data-screen-region="online-presence"]')).toContainText('https://www.samsung.com/support/')
    await expect(page.locator('[data-screen-region="online-presence"]')).toContainText('support@example.com')
    await expect(page.locator('[data-screen-region="brand-identity"]')).toContainText('#1428A0')
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
    await page.locator('#brand-name').fill('Samsung Invalid Submission')
    await page.getByLabel('Website').fill('ftp://invalid.example.test')
    await page.getByLabel('Founded year').fill('1940')
    await page.getByLabel('Support URL').fill('https://submitted.example/support')
    await page.getByLabel('Contact email').fill('submitted@example.com')
    await page.getByLabel('Primary color (optional)', { exact: true }).fill('#AABBCC')
    await page.getByRole('button', { name: 'Save changes', exact: true }).click()

    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${formFixtureId}/edit$`))
    await expect(page.getByLabel('Website')).toHaveAttribute('aria-invalid', 'true')
    await expect(page.locator('#brand-website-error')).toBeVisible()
    await expect(page.getByLabel('Founded year')).toHaveValue('1940')
    await expect(page.getByLabel('Support URL')).toHaveValue('https://submitted.example/support')
    await expect(page.getByLabel('Contact email')).toHaveValue('submitted@example.com')
    await expect(page.getByLabel('Primary color (optional)', { exact: true })).toHaveValue('#AABBCC')
    await expect(page.locator('#brand-name')).toHaveValue('Samsung Invalid Submission')
    await expect(page.getByRole('textbox', { name: 'Name' })).toHaveValue('Samsung Invalid Submission')
    await page.reload()
    await expect(page.locator('#brand-name')).toHaveValue('Samsung Form Fixture')
    await expect(page.getByLabel('Website')).toHaveValue('https://www.samsung.com/')
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

    await page.locator('#brand-name').fill('Samsung Form Updated')
    await page.getByLabel('Website').fill('https://www.samsung.com/global')
    await page.getByLabel('Founded year').fill('1969')
    await page.getByLabel('Support URL').fill('https://www.samsung.com/us/support/')
    await page.getByLabel('Contact email').fill('care@example.com')
    await page.getByLabel('Primary color (optional)', { exact: true }).fill('#ff0000')
    await page.getByRole('button', { name: 'Save changes', exact: true }).click()

    await expect(page.getByText('Brand updated.', { exact: true })).toBeVisible()
    await expect(page.locator('#brand-name')).toHaveValue('Samsung Form Updated')
    await expect(page.getByLabel('Slug')).toHaveValue('samsung-form-fixture')
    await expect(page.getByLabel('Website')).toHaveValue('https://www.samsung.com/global')
    await expect(page.getByLabel('Founded year')).toHaveValue('1969')
    await expect(page.getByLabel('Support URL')).toHaveValue('https://www.samsung.com/us/support/')
    await expect(page.getByLabel('Contact email')).toHaveValue('care@example.com')
    await expect(page.getByLabel('Primary color (optional)', { exact: true })).toHaveValue('#FF0000')
    await expect(page.getByText('Draft', { exact: true })).toBeVisible()
    expect(unexpectedDialogs, 'Save changes must not trigger a native browser confirmation.').toEqual([])
    assertNoPageErrors()
})

test('CA-013 color controls synchronize and optional profile values can be cleared', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${formFixtureId}/edit`)

    const colorText = page.getByLabel('Primary color (optional)', { exact: true })
    const colorPicker = page.getByLabel('Choose Primary color', { exact: true })
    await colorText.fill('#00ff00')
    await expect(colorPicker).toHaveValue('#00ff00')
    await colorPicker.fill('#1428a0')
    await expect(colorText).toHaveValue('#1428A0')

    await page.getByLabel('Founded year').fill('')
    await page.getByLabel('Support URL').fill('')
    await page.getByLabel('Contact email').fill('')
    await colorText.fill('')
    await page.getByRole('button', { name: 'Save changes', exact: true }).click()
    await expect(page.getByText('Brand updated.', { exact: true })).toBeVisible()
    await page.reload()
    await expect(page.getByLabel('Founded year')).toHaveValue('')
    await expect(page.getByLabel('Support URL')).toHaveValue('')
    await expect(page.getByLabel('Contact email')).toHaveValue('')
    await expect(colorText).toHaveValue('')
    assertNoPageErrors()
})

test('CA-013 edit sidebar presents the existing logo and delegates management to CA-014', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${formFixtureId}/edit`)

    await expect(page.getByAltText(/Samsung Form .* logo/)).toBeVisible()
    await expect(page.locator('[data-screen-region="logo-context"] input[type="file"]')).toHaveCount(0)
    await page.getByRole('link', { name: 'Manage Media', exact: true }).click()
    await expect(page).toHaveURL(new RegExp(`/admin/central/brands/${formFixtureId}/media$`))
    await expect(page.locator('[data-screen-id="CA-014"]')).toBeVisible()
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
        await expect(page.locator('#brand-name')).toBeVisible()
        await expect(page.getByRole('combobox', { name: 'Country' })).toBeVisible()
        await expect(page.getByLabel('Primary color (optional)', { exact: true })).toBeVisible()
        await expect(page.getByRole('link', { name: 'Cancel', exact: true })).toBeVisible()
        await expect.poll(
            () => page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
            { message: `${url} must not introduce horizontal page overflow at 390px.` },
        ).toBe(true)
    }

    await page.setViewportSize({ width: 1920, height: 1080 })
    await page.goto(`/admin/central/brands/${formFixtureId}/edit`)
    const workspaceWidth = await page.locator('[data-admin-workspace]').evaluate((element) => element.getBoundingClientRect().width)
    const formCardWidth = await page.locator('[data-screen-region="brand-profile-workspace"]').evaluate((element) => element.getBoundingClientRect().width)
    expect(workspaceWidth).toBeGreaterThan(1500)
    expect(formCardWidth).toBeGreaterThan(1200)

    assertNoPageErrors()
})

test('CA-013 persists create, replace, and clear Parent Company mutations while retaining Organizations', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const ownership = page.locator('[data-screen-region="parent-company"]')

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${ownershipFixtureId}/edit`)
    await expect(ownership).toContainText('No Parent Company assigned')
    await expect(page.getByText('Draft', { exact: true })).toBeVisible()

    await ownership.getByRole('button', { name: 'Create new Organization', exact: true }).click()
    const createDialog = page.getByRole('dialog', { name: 'Create Organization' })
    await createDialog.getByLabel('Organization name').fill('Created Parent 株式会社')
    await createDialog.getByRole('button', { name: 'Create and assign', exact: true }).click()
    await expect(page.getByText('Organization created and assigned as Parent Company.', { exact: true })).toBeVisible()
    await page.reload()
    await expect(ownership).toContainText('Created Parent 株式会社')

    await ownership.getByRole('button', { name: 'Change Parent Company', exact: true }).click()
    const manageDialog = page.getByRole('dialog', { name: 'Manage Parent Company' })
    const organizationPicker = manageDialog.getByRole('combobox', { name: 'Organization' })
    await organizationPicker.fill('Samsung Group International')
    await manageDialog.getByRole('option', { name: 'Samsung Group International', exact: true }).click()
    await manageDialog.getByRole('button', { name: 'Replace Parent Company', exact: true }).click()
    await expect(page.getByText('Parent Company updated.', { exact: true })).toBeVisible()
    await page.reload()
    await expect(ownership).toContainText('Samsung Group International')
    await expect(page.getByText('Draft', { exact: true })).toBeVisible()

    await ownership.getByRole('button', { name: 'Clear Parent Company', exact: true }).click()
    const clearDialog = page.getByRole('dialog', { name: 'Clear Parent Company?' })
    await clearDialog.getByRole('button', { name: 'Clear Parent Company', exact: true }).click()
    await expect(page.getByText('Parent Company cleared.', { exact: true })).toBeVisible()
    await page.reload()
    await expect(ownership).toContainText('No Parent Company assigned')
    await expect(page.getByText('Draft', { exact: true })).toBeVisible()

    await ownership.getByRole('button', { name: 'Assign existing Organization', exact: true }).click()
    const retainedPicker = page.getByRole('dialog', { name: 'Manage Parent Company' }).getByRole('combobox', { name: 'Organization' })
    await retainedPicker.fill('Created Parent 株式会社')
    await expect(page.getByRole('option', { name: 'Created Parent 株式会社', exact: true })).toBeVisible()
    await retainedPicker.fill('Samsung Group International')
    await expect(page.getByRole('option', { name: 'Samsung Group International', exact: true })).toBeVisible()
    await page.getByRole('dialog', { name: 'Manage Parent Company' }).getByRole('button', { name: 'Cancel', exact: true }).click()

    assertNoPageErrors()
})

test('CA-013 Parent Company cancel and validation failure leave ownership unchanged', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    const ownership = page.locator('[data-screen-region="parent-company"]')

    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${ownershipFixtureId}/edit`)
    await expect(ownership).toContainText('No Parent Company assigned')

    await ownership.getByRole('button', { name: 'Create new Organization', exact: true }).click()
    const cancelledCreate = page.getByRole('dialog', { name: 'Create Organization' })
    await cancelledCreate.getByLabel('Organization name').fill('Cancelled Parent Company')
    await cancelledCreate.getByRole('button', { name: 'Cancel', exact: true }).click()
    await page.reload()
    await expect(ownership).toContainText('No Parent Company assigned')

    await ownership.getByRole('button', { name: 'Assign existing Organization', exact: true }).click()
    const manageDialog = page.getByRole('dialog', { name: 'Manage Parent Company' })
    const organizationPicker = manageDialog.getByRole('combobox', { name: 'Organization' })
    await organizationPicker.fill('Cancelled Parent Company')
    await expect(manageDialog.getByText('No matching options.', { exact: true })).toBeVisible()
    await expect(manageDialog.getByRole('option', { name: 'Cancelled Parent Company', exact: true })).toHaveCount(0)
    await organizationPicker.fill('Samsung Group International')
    await manageDialog.getByRole('option', { name: 'Samsung Group International', exact: true }).click()
    await manageDialog.getByRole('button', { name: 'Assign Parent Company', exact: true }).click()
    await expect(ownership).toContainText('Samsung Group International')

    await ownership.getByRole('button', { name: 'Create new Organization', exact: true }).click()
    const invalidCreate = page.getByRole('dialog', { name: 'Create Organization' })
    await invalidCreate.getByLabel('Organization name').fill('   ')
    await invalidCreate.getByRole('button', { name: 'Create and assign', exact: true }).click()
    await expect(page.getByRole('dialog', { name: 'Create Organization' })).toBeVisible()
    await expect(page.locator('#new-parent-company-name-error')).toBeVisible()
    await expect(ownership).toContainText('Samsung Group International')
    await page.reload()
    await expect(ownership).toContainText('Samsung Group International')

    await ownership.getByRole('button', { name: 'Clear Parent Company', exact: true }).click()
    await page.getByRole('dialog', { name: 'Clear Parent Company?' }).getByRole('button', { name: 'Clear Parent Company', exact: true }).click()
    await expect(ownership).toContainText('No Parent Company assigned')

    assertNoPageErrors()
})
