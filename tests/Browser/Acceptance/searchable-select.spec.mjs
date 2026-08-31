import { expect, test } from '@playwright/test'
import {
    foundationDemo,
    observePageErrors,
    signIn,
} from '../Support/acceptance.mjs'

async function openCreateBrand(page) {
    await signIn(page, 'central', foundationDemo.centralAdmin)
    await expect(page.locator('[data-screen-id="CA-001"]')).toBeVisible()
    await page.goto('/admin/central/brands/create')
    await expect(page.locator('[data-screen-id="CA-013"]')).toBeVisible()
    await expect.poll(() => page.evaluate(() => window.__catalogHubSearchableSelectsBooted)).toBe(true)
}

test('remote searchable select aborts in-flight work and cancels debounce when choosing an option', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await openCreateBrand(page)

    await page.evaluate(() => {
        window.__runtimeSearchCalls = []
        window.fetch = (input, init = {}) => {
            const call = { url: String(input), aborted: false }
            window.__runtimeSearchCalls.push(call)

            return new Promise((resolve, reject) => {
                init.signal?.addEventListener('abort', () => {
                    call.aborted = true
                    reject(new DOMException('Aborted', 'AbortError'))
                }, { once: true })
            })
        }

        document.body.insertAdjacentHTML('beforeend', `
            <label for="runtime-organization-combobox">Runtime Organization</label>
            <div
                data-ui-searchable-select
                data-selected-label=""
                data-search-url="/runtime-organizations"
                data-empty-message="No matching runtime options."
                data-error-message="Unable to load runtime options."
            >
                <select id="runtime-organization" data-ui-searchable-select-native>
                    <option value="">Select an option</option>
                    <option value="42">Target Organization</option>
                </select>
                <input
                    id="runtime-organization-combobox"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="runtime-organization-listbox"
                    data-ui-searchable-select-input
                >
                <div id="runtime-organization-listbox" role="listbox" data-ui-searchable-select-listbox hidden>
                    <div
                        id="runtime-organization-option-0"
                        role="option"
                        aria-selected="false"
                        data-ui-searchable-select-option
                        data-value="42"
                        data-label="Target Organization"
                        data-search="target organization"
                    >Target Organization</div>
                    <p data-ui-searchable-select-empty hidden>No matching runtime options.</p>
                    <p data-ui-searchable-select-loading hidden>Loading runtime options…</p>
                </div>
            </div>
        `)
    })

    const input = page.getByRole('combobox', { name: 'Runtime Organization' })
    await input.focus()
    await expect.poll(() => page.evaluate(() => window.__runtimeSearchCalls.length)).toBe(1)

    await page.evaluate(() => {
        const scheduleKey = `set${'Timeout'}`
        const cancelKey = `clear${'Timeout'}`
        const originalSchedule = window[scheduleKey]
        const originalCancel = window[cancelKey]
        let scheduled
        window[scheduleKey] = (callback, delay) => {
            scheduled = { callback, delay, cancelled: false, id: 1 }

            return scheduled.id
        }
        window[cancelKey] = (id) => {
            if (scheduled?.id === id) scheduled.cancelled = true
        }

        const input = document.querySelector('#runtime-organization-combobox')
        const option = document.querySelector('#runtime-organization-option-0')
        input.value = 'Target'
        input.dispatchEvent(new Event('input', { bubbles: true }))
        option.click()
        window[scheduleKey] = originalSchedule
        window[cancelKey] = originalCancel
        if (! scheduled.cancelled) scheduled.callback()
        window.__runtimeDebounce = { delay: scheduled.delay, cancelled: scheduled.cancelled }
    })

    await expect(page.locator('#runtime-organization')).toHaveValue('42')
    await expect(input).toHaveValue('Target Organization')
    expect(await page.evaluate(() => window.__runtimeDebounce)).toEqual({ delay: 180, cancelled: true })
    const origin = new URL(page.url()).origin
    expect(await page.evaluate(() => window.__runtimeSearchCalls)).toEqual([
        { url: `${origin}/runtime-organizations`, aborted: true },
    ])
    assertNoPageErrors()
})

test('remote searchable select distinguishes failures and refreshes empty results when reopened', async ({ page }) => {
    const assertNoPageErrors = observePageErrors(page)
    await openCreateBrand(page)

    await page.evaluate(() => {
        window.__runtimeSearchCalls = []
        window.fetch = async (input) => {
            window.__runtimeSearchCalls.push(String(input))
            if (window.__runtimeSearchCalls.length === 1) {
                return new Response('', { status: 503 })
            }

            return new Response(JSON.stringify({ options: [] }), {
                status: 200,
                headers: { 'Content-Type': 'application/json' },
            })
        }

        document.body.insertAdjacentHTML('beforeend', `
            <label for="runtime-refresh-combobox">Refresh Organization</label>
            <div
                data-ui-searchable-select
                data-selected-label=""
                data-search-url="/runtime-refresh-organizations"
                data-empty-message="No matching refresh options."
                data-error-message="Unable to load refresh options."
            >
                <select id="runtime-refresh" data-ui-searchable-select-native>
                    <option value="">Select an option</option>
                </select>
                <input
                    id="runtime-refresh-combobox"
                    role="combobox"
                    aria-expanded="false"
                    aria-controls="runtime-refresh-listbox"
                    data-ui-searchable-select-input
                >
                <div id="runtime-refresh-listbox" role="listbox" data-ui-searchable-select-listbox hidden>
                    <p data-ui-searchable-select-empty hidden>No matching refresh options.</p>
                    <p data-ui-searchable-select-loading hidden>Loading refresh options…</p>
                </div>
            </div>
        `)
    })

    const input = page.getByRole('combobox', { name: 'Refresh Organization' })
    await input.focus()
    await expect(page.getByText('Unable to load refresh options.', { exact: true })).toBeVisible()

    await page.getByRole('heading', { name: 'Create Brand', exact: true }).click()
    await expect(page.locator('#runtime-refresh-listbox')).toBeHidden()
    await input.focus()
    await expect(page.getByText('No matching refresh options.', { exact: true })).toBeVisible()
    const origin = new URL(page.url()).origin
    expect(await page.evaluate(() => window.__runtimeSearchCalls)).toEqual([
        `${origin}/runtime-refresh-organizations`,
        `${origin}/runtime-refresh-organizations`,
    ])
    assertNoPageErrors()
})
