import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn, resetBrowserFixture } from '../Support/acceptance.mjs'
import {
    activateRtlBrandTranslationLocale,
    addWorkspaceLanguageOptions,
    clearSourceTagline,
    keepOnlyTargetLocale,
    setLongBrandTranslationIdentity,
    workspacePersistence,
} from '../Support/brand-translation-fixture.mjs'

const workspaceBrandId = 24

test.beforeEach(() => resetBrowserFixture())

test.afterEach(() => resetBrowserFixture())

test('CA-015 contains long canonical identity and locale labels without writes at every section width', async ({ page }) => {
    test.slow()
    const assertNoPageErrors = observePageErrors(page)
    setLongBrandTranslationIdentity()
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    const before = workspacePersistence()
    for (const viewport of [
        { width: 1440, height: 1000 },
        { width: 1280, height: 900 },
        { width: 1024, height: 900 },
        { width: 768, height: 1024 },
        { width: 390, height: 844 },
    ]) {
        await page.setViewportSize(viewport)
        await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/de-DE?source=en-US`)
        await expect(page.getByRole('heading', { name: 'Brand Translations', exact: true })).toBeVisible()
        await expect(page.getByLabel('Source language', { exact: true })).toHaveValue('en-US')
        await expect(page.getByLabel('Target language', { exact: true })).toHaveValue('de-DE')
        const headerSlug = page.locator('[data-page-actions] .font-foundation-mono')
        await expect(headerSlug).toBeVisible()
        await expect(headerSlug).toHaveText('long-slug-'.repeat(24))
        await expect(page.locator('#source-language option:checked')).toContainText(`English ${'reference language '.repeat(10)}`)
        await expect(page.locator('#target-language option:checked')).toContainText(`German ${'target language '.repeat(10)}`)
        await expect(page.getByRole('link', { name: 'View Brand', exact: true })).toBeVisible()
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth),
            `Long translation identity must not overflow at ${viewport.width}px.`).toBe(true)
    }
    expect(workspacePersistence()).toEqual(before)
    assertNoPageErrors()
})

test('CA-015 dims the disabled source chevron when only the target locale is active', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    keepOnlyTargetLocale()
    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/de-DE`)
    await expect(page.locator('#source-language')).toBeDisabled()
    await expect(page.locator('#source-language + [data-select-chevron]')).toHaveCSS('opacity', '0.5')
    await expect(page.locator('#target-language')).toBeEnabled()
    await expect(page.locator('#target-language + [data-select-chevron]')).toHaveCSS('opacity', '1')
    assertNoPageErrors()
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

test('CA-015 offers any source and target in two compact menus with twenty active languages and read-only swapping', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/de-DE?source=en-US`)
    const direction = page.locator('[data-screen-region="translation-direction"]')
    const sizes = new Map()
    for (const width of [1440, 768, 390]) {
        await page.setViewportSize({ width, height: 1000 })
        await page.evaluate(() => document.fonts.ready)
        sizes.set(width, (await direction.boundingBox()).height)
    }
    addWorkspaceLanguageOptions()
    const before = workspacePersistence()
    const mutations = []
    page.on('request', (request) => {
        if (request.method() !== 'GET' && request.url().includes('/translations/')) mutations.push(request.url())
    })
    await page.reload()
    await expect(page.locator('#source-language option')).toHaveCount(21)
    await expect(page.locator('#target-language option')).toHaveCount(20)
    await expect(page.getByRole('navigation', { name: 'Translation locales' })).toHaveCount(0)
    for (const width of [1440, 768, 390]) {
        await page.setViewportSize({ width, height: 1000 })
        await page.evaluate(() => document.fonts.ready)
        expect(Math.abs((await direction.boundingBox()).height - sizes.get(width))).toBeLessThan(1)
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        for (const field of await page.locator('[data-translation-field]').all()) {
            const source = await field.locator('.brand-translation-source').boundingBox()
            const target = await field.locator('.brand-translation-target').boundingBox()
            if (width >= 640) expect(source.x + source.width).toBeLessThanOrEqual(target.x)
            else expect(source.y + source.height).toBeLessThanOrEqual(target.y)
        }
        for (const select of await page.locator('.brand-translation-select').all()) {
            const control = await select.locator('select').boundingBox()
            const chevron = await select.locator('[data-select-chevron]').boundingBox()
            expect(control.x + control.width - chevron.x - chevron.width).toBeCloseTo(12, 0)
        }
        if (width >= 1024) {
            await expect(page.locator('.brand-translation-table-heading span')).toHaveText(['Field', 'Source · English', 'Target · German'])
        }
    }
    await page.getByLabel('Source language', { exact: true }).selectOption('de-DE')
    await expect(page).toHaveURL(/translations\/en-US\?source=de-DE$/)
    await expect(page.getByLabel('Source language', { exact: true })).toHaveValue('de-DE')
    await expect(page.getByLabel('Target language', { exact: true })).toHaveValue('en-US')
    await page.getByLabel('Source language', { exact: true }).selectOption('en-US')
    await expect(page).toHaveURL(/translations\/de-DE\?source=en-US$/)
    await page.getByLabel('Target language', { exact: true }).selectOption('bg-BG')
    await expect(page).toHaveURL(/translations\/bg-BG\?source=en-US$/)
    await expect(page.locator('#name')).toHaveValue('')
    await page.getByLabel('Source language', { exact: true }).selectOption('it-IT')
    await expect(page).toHaveURL(/translations\/bg-BG\?source=it-IT$/)
    await expect(page.getByText('No source translation available for Italian (it-IT). Choose another source language.')).toBeVisible()
    await page.getByLabel('Target language', { exact: true }).selectOption('it-IT')
    await expect(page).toHaveURL(/translations\/it-IT\?source=bg-BG$/)
    await page.reload()
    await expect(page.getByLabel('Source language', { exact: true })).toHaveValue('bg-BG')
    await expect(page.getByLabel('Target language', { exact: true })).toHaveValue('it-IT')
    expect(mutations).toEqual([])
    expect(workspacePersistence()).toEqual(before)
    assertNoPageErrors()
})

test('CA-015 offers valid source and target navigation when JavaScript is disabled', async ({ page, browser }) => {
    const assertNoPageErrors = observePageErrors(page)
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    const context = await browser.newContext({ javaScriptEnabled: false, storageState: await page.context().storageState() })
    try {
        const fallback = await context.newPage()
        await fallback.goto(new URL(`/admin/central/brands/${workspaceBrandId}/translations/de-DE?source=en-US`, page.url()).href)
        const before = workspacePersistence()
        await fallback.getByText('Choose languages', { exact: true }).click()
        await fallback.getByRole('navigation', { name: 'Source languages' }).getByRole('link', { name: /Source: German/ }).click()
        await expect(fallback).toHaveURL(/translations\/en-US\?source=de-DE$/)
        await fallback.getByText('Choose languages', { exact: true }).click()
        await fallback.getByRole('navigation', { name: 'Target languages' }).getByRole('link', { name: /Target: German/ }).click()
        await expect(fallback).toHaveURL(/translations\/de-DE\?source=en-US$/)
        expect(workspacePersistence()).toEqual(before)
    } finally {
        await context.close()
    }
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
    const assertNoPageErrors = observePageErrors(page)
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
    const source = before.rows.find((row) => row.locale === 'en-US')
    const target = saved.rows.find((row) => row.locale === 'fr-FR')
    expect(source).toBeDefined()
    expect(target).toBeDefined()
    // Native form submission serializes textarea line breaks as CRLF.
    for (const field of fields) expect(target[field].replace(/\r\n/g, '\n')).toBe(source[field].replace(/\r\n/g, '\n'))
    expect(target.status).toBe('human_reviewed')
    assertNoPageErrors()
})

test('CA-015 preserves source choice through selection, navigation, and validation; skips empty source fields', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
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
    page.once('dialog', (dialog) => dialog.dismiss())
    await page.locator('#source-language').selectOption('de-DE')
    await expect(page.locator('#source-language')).toHaveValue('en-US')
    await expect(page.locator('#tagline')).toHaveValue('Keep this field')
    page.once('dialog', async (dialog) => {
        expect(dialog.message()).toBe('Discard unsaved translation changes?')
        await dialog.accept()
    })
    await page.locator('#source-language').selectOption('de-DE')
    await expect(page).toHaveURL(/source=de-DE$/)
    await expect(page.getByText('You are translating from an outdated source.')).toBeVisible()
    await page.getByLabel('Target language', { exact: true }).selectOption('en-US')
    await expect(page).toHaveURL(/translations\/en-US\?source=de-DE$/)
    await page.getByLabel('Target language', { exact: true }).selectOption('de-DE')
    await expect(page.locator('#source-language')).toHaveValue('en-US')
    await expect(page.locator('#target-language')).toHaveValue('de-DE')
    await page.getByLabel('Source language', { exact: true }).selectOption('de-DE')
    await expect(page).toHaveURL(/translations\/en-US\?source=de-DE$/)
    await expect(page.getByLabel('Source language', { exact: true })).toHaveValue('de-DE')
    await expect(page.getByLabel('Target language', { exact: true })).toHaveValue('en-US')
    await page.reload()
    await expect(page.getByLabel('Source language', { exact: true })).toHaveValue('de-DE')
    await expect(page.getByLabel('Target language', { exact: true })).toHaveValue('en-US')
    assertNoPageErrors()
})


test('CA-015 protects unsaved edits, copied text, and Save as when either language changes', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    const url = `/admin/central/brands/${workspaceBrandId}/translations/fr-FR?source=en-US`
    await page.goto(url)
    const before = workspacePersistence()
    const mutations = []
    page.on('request', (request) => {
        if (request.method() !== 'GET') mutations.push(request.url())
    })
    const dialogs = []
    page.on('dialog', async (dialog) => {
        dialogs.push(dialog.message())
        await dialog.dismiss()
    })
    await page.locator('#tagline').fill('Unsaved draft')
    for (const [selector, selected, previous] of [['#source-language', 'de-DE', 'en-US'], ['#target-language', 'en-US', 'fr-FR']]) {
        await page.locator(selector).selectOption(selected)
        await expect(page).toHaveURL(new RegExp('translations/fr-FR\\?source=en-US$'))
        await expect(page.locator(selector)).toHaveValue(previous)
        await expect(page.locator('#tagline')).toHaveValue('Unsaved draft')
    }
    await page.locator('#tagline').fill('')
    await page.locator('#status').selectOption('machine_translated')
    await page.locator('#source-language').selectOption('de-DE')
    await expect(page.locator('#source-language')).toHaveValue('en-US')
    await page.locator('#status').selectOption('human_reviewed')
    await page.getByRole('button', { name: 'Copy all from Source', exact: true }).click()
    await page.locator('#target-language').selectOption('en-US')
    await expect(page.locator('#target-language')).toHaveValue('fr-FR')
    expect(dialogs).toEqual(Array(4).fill('Discard unsaved translation changes?'))
    expect(mutations).toEqual([])
    expect(workspacePersistence()).toEqual(before)
    page.removeAllListeners('dialog')
    page.once('dialog', async (dialog) => {
        expect(dialog.message()).toBe('Discard unsaved translation changes?')
        await dialog.accept()
    })
    await page.locator('#source-language').selectOption('fr-FR')
    await expect(page).toHaveURL(/translations\/en-US\?source=fr-FR$/)
    expect(workspacePersistence()).toEqual(before)
    await page.goto(url)
    await page.locator('#tagline').fill('Temporary draft')
    await page.locator('#tagline').fill('')
    page.on('dialog', async (dialog) => {
        throw new Error(`Unexpected confirmation for restored fields or explicit Save: ${dialog.message()}`)
    })
    await page.locator('#source-language').selectOption('de-DE')
    await expect(page).toHaveURL(/translations\/fr-FR\?source=de-DE$/)
    await page.locator('#name').fill('French draft')
    await page.locator('#brand-translation-form').getByRole('button', { name: 'Save translation', exact: true }).click()
    await expect(page.getByText('Translation saved.', { exact: true })).toBeVisible()
    assertNoPageErrors()
})

test('CA-015 counters match native UTF-16 maxlength enforcement for emoji', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto(`/admin/central/brands/${workspaceBrandId}/translations/fr-FR?source=en-US`)
    const before = workspacePersistence()
    await page.locator('#tagline').fill('A😀B')
    await expect(page.locator('[data-brand-translation-counter=tagline]')).toHaveText('4 / 255')
    await page.locator('#tagline').fill('A'.repeat(253))
    await page.locator('#tagline').press('End')
    await page.locator('#tagline').pressSequentially('😀B')
    await expect(page.locator('#tagline')).toHaveValue('A'.repeat(253) + '😀')
    await expect(page.locator('[data-brand-translation-counter=tagline]')).toHaveText('255 / 255')
    expect(workspacePersistence()).toEqual(before)
    assertNoPageErrors()
})
