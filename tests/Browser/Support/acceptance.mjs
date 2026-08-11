import { expect } from '@playwright/test'

export const foundationDemo = Object.freeze({
    password: 'cataloghub-foundation-demo',
    centralAdmin: 'central-admin@demo.cataloghub.test',
    siteAdmin: 'site-admin@demo.cataloghub.test',
    noAccess: 'no-access@demo.cataloghub.test',
    disabled: 'disabled@demo.cataloghub.test',
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
