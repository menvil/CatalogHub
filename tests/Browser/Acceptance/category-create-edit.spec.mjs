import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, resetBrowserFixture, signIn } from '../Support/acceptance.mjs'

// category-create-edit-v1 stable IDs (see tests/Support/CategoryCreateEditFixture.php).
const ids = { electronics: 196001, smartHome: 196004, displays: 196006, televisions: 196008, monitors: 196009, gamingMonitors: 196011 }

test.afterAll(() => resetBrowserFixture())

async function signInAdmin(page) {
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
}

function guardDialogs(page) {
    const dialogs = []
    page.on('dialog', (dialog) => {
        dialogs.push(`${dialog.type()}: ${dialog.message()}`)
        void dialog.dismiss()
    })

    return () => expect(dialogs, 'CA-018 must not use native browser dialogs.').toEqual([])
}

async function openEdit(page, id) {
    await page.goto(`/admin/central/categories/${id}/edit`)
    await expect(page.locator('[data-screen-id="CA-018"][data-category-form-mode="edit"]')).toBeVisible()
}

async function chooseParent(page, search, id) {
    const parent = page.getByRole('combobox', { name: 'Parent Category' })
    await parent.fill(search)
    const option = page.locator(`#category-parent-listbox [data-value="${id}"]`)
    const label = await option.getAttribute('data-label')
    await option.click()
    await expect(parent).toHaveValue(label)
}

async function choosePosition(page, label) {
    await page.locator('#category-display-position-trigger').click()
    await page.locator('#category-display-position-menu').getByRole('option', { name: label, exact: true }).click()
}

async function save(page) {
    await page.getByRole('button', { name: 'Save Category', exact: true }).click()
}

// Each mutating flow starts from the pristine fixture.
test.describe('CA-018 persisted flows', () => {
    test.beforeEach(() => resetBrowserFixture('category-create-edit-v1'))

    test('CA-018 creates a real root Draft Category from CA-016 and lands on CA-017', async ({ page }) => {
        const noErrors = observePageErrors(page)
        const noDialogs = guardDialogs(page)
        await signInAdmin(page)
        await page.goto('/admin/central/categories')
        await page.getByRole('link', { name: 'New Category', exact: true }).click()
        await expect(page).toHaveURL(/\/admin\/central\/categories\/create$/)
        const form = page.locator('#category-form')
        await expect(page.locator('[data-screen-id="CA-018"][data-category-form-mode="create"][data-fixture-version="category-create-edit-v1"]')).toBeVisible()
        await expect(page.getByRole('combobox', { name: 'Parent Category' })).toHaveValue('')
        for (const absent of ['Category Code', 'Category Icon', 'Upload Icon', 'Color', 'Published', 'SEO', 'URL Template', 'Category ID', 'Last updated']) {
            await expect(form.getByText(absent, { exact: false })).toHaveCount(0)
        }
        await page.getByLabel('Category Name').fill('Webcams & Streaming')
        await expect(page.getByLabel('Slug')).toHaveValue('webcams-and-streaming')
        await save(page)

        await expect(page).toHaveURL(/\/admin\/central\/categories\/\d+$/)
        const id = Number(page.url().split('/').pop())
        await expect(page.locator('[data-screen-id="CA-017"]')).toBeVisible()
        await expect(page.getByText(`Category #${id} created as Draft.`).first()).toBeVisible()
        await expect(page.locator('[data-category-lifecycle]')).toContainText('Draft')
        await expect(page.locator('[data-category-schema-state]')).toContainText('Draft')
        await expect(page.locator('[data-category-id]')).toHaveText(String(id))
        await page.goto('/admin/central/categories?q=Webcams')
        await expect(page.locator('tr[data-row-id]').filter({ hasText: 'Webcams & Streaming' })).toHaveCount(1)
        await page.goto(`/admin/central/categories/${id}`)
        await page.getByRole('link', { name: 'Edit Category', exact: true }).click()
        await expect(page).toHaveURL(new RegExp(`/admin/central/categories/${id}/edit$`))
        await expect(page.getByLabel('Slug')).toHaveValue('webcams-and-streaming')
        noDialogs()
        noErrors()
    })

    test('CA-018 edits identity with a reviewed slug routing impact and keeps the stable ID', async ({ page }) => {
        const noErrors = observePageErrors(page)
        const noDialogs = guardDialogs(page)
        await signInAdmin(page)
        await openEdit(page, ids.monitors)
        await expect(page.getByLabel('Slug')).toHaveValue('desktop-monitors')
        await page.getByLabel('Category Name').fill('Computer Monitors')
        await page.getByLabel('Slug').fill('computer-monitors')
        const impact = page.locator('[data-category-slug-impact]')
        await expect(impact).toBeVisible()
        await expect(impact).toContainText('https://display-world.test/en-US/categories/desktop-monitors')
        await expect(impact).toContainText('https://display-world.test/en-US/categories/computer-monitors')
        await expect(impact).toContainText('keeps bildschirme')
        await expect(impact).toContainText('No automatic HTTP redirect is created.')

        await save(page)
        await expect(page).toHaveURL(new RegExp(`/categories/${ids.monitors}/edit\\?slug_preview=computer-monitors$`))
        await expect(page.getByText('Confirm that you reviewed the routing impact of this slug change.')).toBeVisible()
        await expect(page.getByLabel('Category Name')).toHaveValue('Computer Monitors')
        await page.getByLabel('I reviewed this routing impact and understand that no redirect is created.').check()
        await save(page)

        await expect(page).toHaveURL(new RegExp(`/admin/central/categories/${ids.monitors}$`))
        await expect(page.getByText('Category updated.').first()).toBeVisible()
        await expect(page.locator('[data-category-id]')).toHaveText(String(ids.monitors))
        await expect(page.locator('[data-category-slug]')).toHaveText('computer-monitors')
        await page.goto('/admin/central/categories?q=Computer+Monitors')
        await expect(page.locator('tr[data-row-id]').filter({ hasText: 'Computer Monitors' })).toHaveCount(1)
        noDialogs()
        noErrors()
    })

    test('CA-018 reparents and then reorders safely within the sibling group', async ({ page }) => {
        const noErrors = observePageErrors(page)
        await signInAdmin(page)
        await openEdit(page, ids.monitors)
        await expect(page.locator('#category-parent-listbox [data-value="' + ids.monitors + '"]')).toHaveCount(0)
        await chooseParent(page, 'Electronics', ids.electronics)
        await expect(page.locator('[data-category-order-parent-note]')).toBeVisible()
        await save(page)
        await expect(page).toHaveURL(new RegExp(`/admin/central/categories/${ids.monitors}$`))
        const breadcrumbs = page.locator('[data-central-header-breadcrumbs]')
        await expect(breadcrumbs).toContainText('Electronics')
        await expect(breadcrumbs).not.toContainText('Displays')

        await openEdit(page, ids.televisions)
        await expect(page.getByRole('list', { name: 'Current sibling order' })).toHaveText(/1\s*Televisions\s*2\s*Projectors/)
        await choosePosition(page, '2 of 2 — after Projectors')
        await save(page)
        await expect(page.getByText('Category updated.').first()).toBeVisible()
        await openEdit(page, ids.televisions)
        await expect(page.getByRole('list', { name: 'Current sibling order' })).toHaveText(/1\s*Projectors\s*2\s*Televisions/)
        noErrors()
    })

    test('CA-018 rejects a tampered descendant parent, a stale revision and a duplicate slug without writes', async ({ page, context }) => {
        const noErrors = observePageErrors(page)
        await signInAdmin(page)
        await openEdit(page, ids.monitors)
        await expect(page.locator(`#category-parent-listbox [data-value="${ids.gamingMonitors}"]`)).toHaveCount(0)
        await page.evaluate((descendant) => {
            const select = document.querySelector('select[name="parent_id"]')
            select.add(new Option('Gaming Monitors', String(descendant), true, true))
            document.querySelector('[data-category-destination-revision]').value = '0'
        }, ids.gamingMonitors)
        await save(page)
        await expect(page.getByText('Category ancestry must be acyclic.')).toBeVisible()
        await page.goto(`/admin/central/categories/${ids.monitors}`)
        await expect(page.locator('[data-central-header-breadcrumbs]')).toContainText('Displays')

        await openEdit(page, ids.monitors)
        const other = await context.newPage()
        await openEdit(other, ids.televisions)
        await choosePosition(other, '3 of 3 — after Projectors')
        await save(other)
        await expect(other.getByText('Category updated.').first()).toBeVisible()
        await other.close()
        await choosePosition(page, '1 of 3 — before Televisions')
        await save(page)
        await expect(page.locator('[data-category-form-errors]')).toContainText('The sibling scope changed. Reload before saving.')
        await page.getByRole('link', { name: 'Discard them and reload the current Category' }).click()
        await expect(page.getByRole('list', { name: 'Current sibling order' })).toHaveText(/1\s*Monitors\s*2\s*Projectors\s*3\s*Televisions/)

        await openEdit(page, ids.smartHome)
        await page.getByLabel('Slug').fill('electronics')
        await expect(page.locator('[data-slug-impact-no-acknowledgement]')).toBeVisible()
        await save(page)
        await expect(page.getByText('The slug has already been taken.')).toBeVisible()
        await page.goto(`/admin/central/categories/${ids.smartHome}`)
        await expect(page.locator('[data-category-slug]')).toHaveText('smart-home')
        noErrors()
    })
})

// Read-only checks share one fixture reset; none of them writes.
test.describe('CA-018 read-only checks', () => {
    test.beforeAll(() => resetBrowserFixture('category-create-edit-v1'))

    test('CA-018 cancel writes nothing and retired routes only redirect', async ({ page }) => {
        const noErrors = observePageErrors(page)
        await signInAdmin(page)
        await openEdit(page, ids.monitors)
        await page.getByLabel('Category Name').fill('Not saved')
        await page.locator('[data-category-form-cancel]').click()
        await expect(page).toHaveURL(new RegExp(`/admin/central/categories/${ids.monitors}$`))
        await expect(page.locator('h1')).toHaveText('Monitors')
        await page.goto('/admin/central/categories/create')
        await page.locator('[data-category-form-cancel]').click()
        await expect(page).toHaveURL(/\/admin\/central\/categories$/)

        await page.goto('/admin/central/central-categories/create')
        await expect(page).toHaveURL(/\/admin\/central\/categories\/create$/)
        await page.goto(`/admin/central/central-categories/${ids.monitors}/edit`)
        await expect(page).toHaveURL(new RegExp(`/admin/central/categories/${ids.monitors}/edit$`))
        await expect(page.locator('[data-screen-id="CA-018"]')).toBeVisible()
        await page.goto(`/admin/central/central-categories/${ids.monitors}/schema`)
        await expect(page.getByRole('heading', { name: 'Category Schema Builder' })).toBeVisible()
        noErrors()
    })

    test('CA-018 denies an actor without Category mutation capability', async ({ page }) => {
        await signIn(page, 'central', foundationDemo.translator)
        await expect(page).not.toHaveURL(/\/login$/)
        for (const path of ['/admin/central/categories/create', `/admin/central/categories/${ids.monitors}/edit`]) {
            const response = await page.goto(path)
            expect(response?.status()).toBe(403)
        }
    })

    for (const [width, height] of [[1440, 1000], [1024, 900], [768, 1024], [390, 844]]) {
        test(`CA-018 create and edit fit ${width}px without page overflow`, async ({ page }) => {
            const noErrors = observePageErrors(page)
            await page.setViewportSize({ width, height })
            await signInAdmin(page)
            for (const path of ['/admin/central/categories/create', `/admin/central/categories/${ids.monitors}/edit`]) {
                await page.goto(path)
                await expect(page.locator('[data-screen-id="CA-018"]')).toBeVisible()
                expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
                await page.locator('[data-category-form-actions]').scrollIntoViewIfNeeded()
                await expect(page.getByRole('button', { name: 'Save Category', exact: true })).toBeInViewport()
                await expect(page.locator('[data-category-form-cancel]')).toBeInViewport()
            }
            noErrors()
        })
    }
})
