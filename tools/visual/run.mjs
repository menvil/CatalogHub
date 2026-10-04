import { execFile, spawn } from 'node:child_process'
import { randomUUID } from 'node:crypto'
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
const lifecycle = new VisualRunLifecycle(async (id) => {
    try {
        await execute('docker', ['rm', '--force', id], { timeout: 10_000, killSignal: 'SIGKILL' })
    } catch (error) {
        // A cancelled create or an already auto-removed container is absent.
        if (!/No such container/i.test(error.stderr ?? '')) throw error
    }
})

async function docker(args, timeout = 10_000) {
    lifecycle.assertRunning()
    const execution = execute('docker', args, { timeout, killSignal: 'SIGKILL', maxBuffer: 1024 * 1024 })
    lifecycle.startupChild = execution.child
    try {
        return await execution
    } finally {
        lifecycle.startupChild = undefined
    }
}

try {
    console.log(`Visual renderer: ${visualEnvironment.imageTag}, ${visualEnvironment.platform}, ${visualEnvironment.image}`)
    const artifacts = resolve(root, 'storage/logs/visual-artifacts')
    mkdirSync(artifacts, { recursive: true })
    writeFileSync(resolve(artifacts, 'renderer.json'), JSON.stringify(visualEnvironment, null, 2) + '\n')
    // Know the owned container before creation, even if its CLI is cancelled
    // before Docker returns an ID. Never address another workspace's container.
    const container = `cataloghub-visual-${randomUUID()}`
    lifecycle.container = container
    await docker([
        'run', '--name', container, '--detach', '--rm', '--init', '--ipc=host', '--platform', visualEnvironment.platform,
        '--publish', '127.0.0.1::3000', '--workdir', '/opt/cataloghub',
        '--volume', `${resolve(root, 'node_modules/playwright')}:/opt/cataloghub/node_modules/playwright:ro`,
        '--volume', `${resolve(root, 'node_modules/playwright-core')}:/opt/cataloghub/node_modules/playwright-core:ro`,
        visualEnvironment.image, 'node', 'node_modules/playwright/cli.js',
        'run-server', '--port', '3000', '--host', '0.0.0.0', '--unsafe',
    ], 180_000)
    lifecycle.assertRunning()
    const port = (await docker(['port', container, '3000/tcp'])).stdout.trim().split(':').at(-1)
    lifecycle.assertRunning()
    const deadline = Date.now() + 60_000
    while (true) {
        const logs = await docker(['logs', container])
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
    console.error(lifecycle.interrupted ? 'Visual run interrupted.' : error.message)
    process.exitCode = lifecycle.interrupted ? 130 : 1
} finally {
    let cleanupFailed = false
    try {
        await lifecycle.dispose()
    } catch (error) {
        console.error(`Visual renderer cleanup failed: ${error.message}`)
        // Preserve a test failure or interruption; a successful run must fail
        // if its detached renderer could not be stopped.
        cleanupFailed = true
    }
    if (!process.exitCode && lifecycle.interrupted) process.exitCode = 130
    if (!process.exitCode && cleanupFailed) process.exitCode = 1
}
