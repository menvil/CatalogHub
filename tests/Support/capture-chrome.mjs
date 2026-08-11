import { writeFile } from 'node:fs/promises'
import { chromium } from '@playwright/test'

const [chrome, url, capture, widthValue, heightValue, dom] = process.argv.slice(2)
const width = Number.parseInt(widthValue, 10)
const height = Number.parseInt(heightValue, 10)

if (!chrome || !url || !capture || !Number.isInteger(width) || !Number.isInteger(height)) {
    throw new Error('Usage: capture-chrome.mjs <chrome> <url> <capture> <width> <height> [dom]')
}

const browser = await chromium.launch({
    executablePath: chrome,
    headless: true,
    args: ['--disable-gpu', '--hide-scrollbars'],
})

try {
    const context = await browser.newContext({
        viewport: { width, height },
        deviceScaleFactor: 1,
        locale: 'en-US',
        timezoneId: 'UTC',
        colorScheme: 'light',
        reducedMotion: 'reduce',
    })
    const page = await context.newPage()
    await page.goto(url, { waitUntil: 'networkidle' })
    await page.evaluate(() => document.fonts.ready)
    await page.addStyleTag({
        content: `
            *, *::before, *::after {
                animation-duration: 0s !important;
                caret-color: transparent !important;
                transition-duration: 0s !important;
            }
            html { scrollbar-width: none !important; }
            ::-webkit-scrollbar { display: none !important; }
        `,
    })
    await page.screenshot({ path: capture, animations: 'disabled' })

    if (dom) {
        await writeFile(dom, await page.content())
    }

    await context.close()
} finally {
    await browser.close()
}
