import { execFile, spawn } from 'node:child_process'
import { once } from 'node:events'
import { mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import { resolve } from 'node:path'
import { promisify } from 'node:util'
import { validateVisualRun, visualEnvironment } from './environment.mjs'
import { VisualRunLifecycle } from './lifecycle.mjs'

const execute = promisify(execFile)
const root = resolve(import.meta.dirname, '../..')
const args = process.argv.slice(2)
const version = JSON.parse(readFileSync(resolve(root, 'node_modules/playwright/package.json'), 'utf8')).version
validateVisualRun(version, args, Boolean(process.env.CI))
const lifecycle = new VisualRunLifecycle((id) => execute('docker', ['stop', '--time', '3', id], { timeout: 10_000 }))

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
    const container = result.stdout.trim()
    lifecycle.container = container
    lifecycle.assertRunning()
    const port = (await execute('docker', ['port', container, '3000/tcp'])).stdout.trim().split(':').at(-1)
    lifecycle.assertRunning()
    const deadline = Date.now() + 60_000
    while (true) {
        const logs = await execute('docker', ['logs', container])
        lifecycle.assertRunning()
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
    lifecycle.assertRunning()
    const child = spawn(process.execPath, [resolve(root, 'node_modules/playwright/cli.js'), 'test', '--config=playwright.config.mjs', '--project=visual', ...args], {
        cwd: root, env: environment, stdio: 'inherit',
    })
    lifecycle.child = child
    const [status] = await once(child, 'exit')
    process.exitCode = lifecycle.interrupted ? 130 : status ?? 1
} catch (error) {
    console.error(error.message)
    process.exitCode = lifecycle.interrupted ? 130 : 1
} finally {
    await lifecycle.dispose()
}
