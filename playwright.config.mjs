import { existsSync } from 'node:fs'
import { defineConfig } from '@playwright/test'
import { visualEnvironment } from './tools/visual/environment.mjs'

const remoteVisualBrowser = process.env.CATALOGHUB_VISUAL_IMAGE === visualEnvironment.image && Boolean(process.env.PW_TEST_CONNECT_WS_ENDPOINT)
const emulatedVisualBrowser = remoteVisualBrowser && process.arch !== 'x64'
const projects = process.argv.flatMap((arg, index, args) => arg.startsWith('--project=') ? [arg.slice(10)] : arg === '--project' ? [args[index + 1]] : [])
if ((projects.length === 0 || projects.includes('visual')) && !remoteVisualBrowser) {
    throw new Error('Visual screenshots require the pinned Linux renderer. Use npm run test:visual or npm run test:visual:update.')
}
const configuredBrowser = process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
const macChrome = '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
const executablePath = remoteVisualBrowser ? undefined : configuredBrowser || (existsSync(macChrome) ? macChrome : undefined)
const port = Number.parseInt(process.env.CATALOGHUB_BROWSER_PORT ?? '', 10)
const foundationHosts = [
    'tech-germany.test',
    'www.tech-germany.test',
    'monitors-germany.test',
    'www.monitors-germany.test',
    'archived-germany.test',
    'unknown.cataloghub.test',
]

if (![8014, 8015].includes(port)) {
    throw new Error('CATALOGHUB_BROWSER_PORT must be 8014 or 8015.')
}

const baseURL = `http://127.0.0.1:${port}`

export default defineConfig({
    fullyParallel: false,
    workers: 1,
    timeout: emulatedVisualBrowser ? 120_000 : 30_000,
    expect: {
        timeout: 10_000,
    },
    forbidOnly: Boolean(process.env.CI),
    retries: 0,
    reporter: 'line',
    snapshotPathTemplate: '{testDir}/../baselines/{arg}{ext}',
    webServer: {
        command: `node tests/Browser/Support/start-server.mjs ${port}`,
        url: `${baseURL}/admin/central/login`,
        reuseExistingServer: false,
        timeout: 120_000,
        gracefulShutdown: {
            signal: 'SIGTERM',
            timeout: 5_000,
        },
        stdout: 'pipe',
        stderr: 'pipe',
    },
    use: {
        baseURL,
        browserName: 'chromium',
        headless: true,
        locale: 'en-US',
        timezoneId: 'UTC',
        colorScheme: 'light',
        reducedMotion: 'reduce',
        deviceScaleFactor: 1,
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
        launchOptions: {
            ...(executablePath ? { executablePath } : {}),
            args: [`--host-resolver-rules=${foundationHosts.map((host) => `MAP ${host} 127.0.0.1`).join(',')}`],
        },
    },
    projects: [
        {
            name: 'browser',
            testDir: './tests/Browser',
            testMatch: '**/*.spec.mjs',
            outputDir: 'storage/logs/browser-artifacts',
            use: {
                viewport: { width: 1280, height: 900 },
            },
        },
        {
            name: 'visual',
            testDir: './tests/Visual/playwright',
            testMatch: '**/*.visual.spec.mjs',
            outputDir: 'storage/logs/visual-artifacts/playwright',
            use: {
                viewport: { width: 1280, height: 900 },
            },
        },
    ],
})
