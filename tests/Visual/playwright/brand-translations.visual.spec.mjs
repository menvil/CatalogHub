import { expect, test } from '@playwright/test'
import { foundationDemo, observePageErrors, signIn } from '../../Browser/Support/acceptance.mjs'
import { activateRtlBrandTranslationLocale, restoreDefaultBrandTranslationLocales } from '../../Browser/Support/brand-translation-fixture.mjs'

const workspaceUrl = '/admin/central/brands/24/translations'
const states = [
    { state: 'outdated', target: 'de-DE', source: 'en-US', width: 1440, height: 1000 },
    { state: 'missing', target: 'fr-FR', source: 'en-US', width: 1440, height: 1000 },
    { state: 'approved', target: 'en-US', source: 'de-DE', width: 1440, height: 1000 },
    { state: 'outdated', target: 'de-DE', source: 'en-US', width: 768, height: 1024 },
    { state: 'outdated', target: 'de-DE', source: 'en-US', width: 390, height: 844 },
    { state: 'rtl', target: 'ar-SA', source: 'en-US', width: 1440, height: 1000 },
]

test.afterEach(() => restoreDefaultBrandTranslationLocales())

for (const state of states) {
    test(`CA-015 v3 ${state.state} ${state.width}px source to target workspace`, async ({ page }) => {
        const assertNoPageErrors = observePageErrors(page)
        await page.setViewportSize({ width: state.width, height: state.height })
        await signIn(page, 'central', foundationDemo.centralAdmin)
        await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
        if (state.state === 'rtl') activateRtlBrandTranslationLocale()
        await page.goto(`${workspaceUrl}/${state.target}?source=${state.source}`)
        await expect(page.locator('[data-brand-translations-fixture="brand-translations-v3"]')).toBeVisible()
        await expect(page.locator('#source-language')).toHaveValue(state.source)
        await expect(page.getByLabel('Target language', { exact: true })).toHaveValue(state.target)
        await expect(page.locator('[data-brand-translation-language-selector]')).toHaveCount(2)
        await expect(page.locator('[data-source-field=tagline]')).not.toHaveText('No source value')
        if (state.state === 'missing') await expect(page.locator('#name')).toHaveValue('')
        if (state.state === 'approved') await expect(page.getByText('You are translating from an outdated source.')).toBeVisible()
        if (state.state === 'rtl') {
            await expect(page.locator('#name')).toHaveAttribute('dir', 'rtl')
            await expect(page.locator('[data-source-field=name]')).toHaveAttribute('dir', 'ltr')
        }
        await page.evaluate(async () => {
            await document.fonts.ready
            const referenceFontLoaded = Array.from(document.fonts).some((face) => face.family.replaceAll('"', '') === 'Instrument Sans' && face.status === 'loaded')
            if (! referenceFontLoaded) throw new Error('CA-015 reference font did not load.')
        })
        await page.addStyleTag({ content: '*,*::before,*::after{animation-duration:0s!important;caret-color:transparent!important;transition-duration:0s!important}html{scrollbar-width:none}::-webkit-scrollbar{display:none}' })
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth)).toBe(true)
        await expect(page).toHaveScreenshot([`ca-015__${state.state}__${state.width}x${state.height}.png`], {
            animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.03,
        })
        if (state.width < 1024) {
            // A viewport reference covers navigation; this additional capture checks every stacked field and the rail.
            await expect(page).toHaveScreenshot([`ca-015__${state.state}-fields__${state.width}x${state.height}.png`], {
                fullPage: true, animations: 'disabled', scale: 'css', maxDiffPixelRatio: 0.03,
            })
        }
        assertNoPageErrors()
    })
}
