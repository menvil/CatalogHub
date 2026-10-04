import { execFile, spawn } from 'node:child_process'
import { once } from 'node:events'
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { promisify } from 'node:util'
import { validateVisualRun, visualEnvironment } from './environment.mjs'

const execute = promisify(execFile)
const root = resolve(import.meta.dirname, '../..')
const args = process.argv.slice(2)
const version = JSON.parse(readFileSync(resolve(root, 'node_modules/playwright/package.json'), 'utf8')).version
validateVisualRun(version, args, Boolean(process.env.CI))
let container
let child

async function stop() {
    if (container) {
        const id = container
        container = undefined
        await execute('docker', ['stop', '--time', '3', id]).catch(() => {})
    }
}

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => {
        // Playwright handles SIGINT with fixture and web-server teardown.
        if (child) child.kill('SIGINT')
        else void stop()
    })
}

try {
    console.log(`Visual renderer: ${visualEnvironment.imageTag}, ${visualEnvironment.platform}, ${visualEnvironment.image}`)
    const artifacts = resolve(root, 'storage/logs/visual-artifacts')
    mkdirSync(artifacts, { recursive: true })
    writeFileSync(resolve(artifacts, 'renderer.json'), JSON.stringify(visualEnvironment, null, 2) + '\n')
    const result = await execute('docker', [
        'run', '--detach', '--rm', '--init', '--ipc=host', '--platform', visualEnvironment.platform,
        '--publish', '127.0.0.1::3000', '--workdir', '/opt/cataloghub',
        '--volume', `${resolve(root, 'node_modules/playwright')}:/opt/cataloghub/node_modules/playwright:ro`,
        '--volume', `${resolve(root, 'node_modules/playwright-core')}:/opt/cataloghub/node_modules/playwright-core:ro`,
        visualEnvironment.image, 'node', 'node_modules/playwright/cli.js',
        'run-server', '--port', '3000', '--host', '0.0.0.0', '--unsafe',
    ], { maxBuffer: 1024 * 1024 })
    container = result.stdout.trim()
    const port = (await execute('docker', ['port', container, '3000/tcp'])).stdout.trim().split(':').at(-1)
    const deadline = Date.now() + 60_000
    while (true) {
        const logs = await execute('docker', ['logs', container])
        if (logs.stdout.includes('Listening on ws://')) break
        if (Date.now() >= deadline) throw new Error(`Pinned visual browser did not start. ${logs.stdout}${logs.stderr}`)
        await new Promise((done) => setTimeout(done, 200))
    }
    const environment = {
        ...process.env,
        CATALOGHUB_BROWSER_PORT: '8015',
        CATALOGHUB_VISUAL_IMAGE: visualEnvironment.image,
        PW_TEST_CONNECT_WS_ENDPOINT: `ws://127.0.0.1:${port}/`,
        PW_TEST_CONNECT_EXPOSE_NETWORK: '<loopback>',
    }
    delete environment.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH
    delete environment.PW_TEST_CONNECT_HEADERS
    child = spawn(process.execPath, [resolve(root, 'node_modules/playwright/cli.js'), 'test', '--config=playwright.config.mjs', '--project=visual', ...args], {
        cwd: root, env: environment, stdio: 'inherit',
    })
    const [status] = await once(child, 'exit')
    process.exitCode = status ?? 1
} catch (error) {
    console.error(error.message)
    process.exitCode = 1
} finally {
    await stop()
}
