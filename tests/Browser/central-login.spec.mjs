import { expect, test } from '@playwright/test'

test('Central operator can sign in through the real browser', async ({ page }) => {
    await page.goto('/admin/central/login')
    await expect(page.locator('[data-auth-screen="central-login"]')).toBeVisible()

    await page.getByLabel(/email/i).fill('central-admin@fixture.test')
    await page.getByRole('textbox', { name: /^password/i }).fill('password')
    await page.getByRole('button', { name: /sign in/i }).click()

    await expect(page).toHaveURL(/\/admin\/central(?:\/|$)/)
    await expect(page.locator('[data-presentation-context="central-admin"]')).toBeVisible()
})
