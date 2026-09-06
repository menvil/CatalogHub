import { expect } from '@playwright/test'
import { execFileSync } from 'node:child_process'
import { resolve } from 'node:path'

export const foundationDemo = Object.freeze({
    password: 'cataloghub-foundation-demo',
    centralAdmin: 'central-admin@demo.cataloghub.test',
    siteAdmin: 'site-admin@demo.cataloghub.test',
    translator: 'translator@demo.cataloghub.test',
    noAccess: 'no-access@demo.cataloghub.test',
    disabled: 'disabled@demo.cataloghub.test',
})

export const foundationSites = Object.freeze({
    techId: 1,
    monitorsId: 2,
    archivedId: 3,
})

export function observePageErrors(page) {
    const errors = []

    page.on('console', (message) => {
        if (message.type() === 'error') {
            errors.push(`console: ${message.text()}`)
        }
    })
    page.on('pageerror', (error) => errors.push(`page: ${error.message}`))

    return () => expect(errors, 'Browser flow emitted console or page errors.').toEqual([])
}

export async function signIn(page, panel, email) {
    await page.goto(`/admin/${panel}/login`)
    await page.getByLabel(/email/i).fill(email)
    await page.getByRole('textbox', { name: /^password/i }).fill(foundationDemo.password)
    await page.getByRole('button', { name: /sign in/i }).click()
}

export async function captureAcceptanceScreenshot(page, testInfo, name) {
    await page.evaluate(() => document.fonts.ready)
    const path = testInfo.outputPath(`${name}.png`)
    await page.screenshot({ path, fullPage: true })
    await testInfo.attach(name, { path, contentType: 'image/png' })
}

export function resetBrowserFixture() {
    const port = Number.parseInt(process.env.CATALOGHUB_BROWSER_PORT ?? '', 10)

    if (![8014, 8015].includes(port)) {
        throw new Error('The deterministic browser fixture requires the Browser harness port.')
    }

    const root = resolve(import.meta.dirname, '../../..')
    const database = resolve(root, `storage/logs/browser-harness-${port}.sqlite`)

    execFileSync('php', ['tests/Browser/Support/bootstrap.php'], {
        cwd: root,
        env: {
            ...process.env,
            APP_ENV: 'testing',
            APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            APP_URL: `http://127.0.0.1:${port}`,
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: database,
            DB_URL: '',
            CACHE_STORE: 'array',
            QUEUE_CONNECTION: 'sync',
            SESSION_DRIVER: 'file',
        },
        stdio: 'pipe',
    })
}
