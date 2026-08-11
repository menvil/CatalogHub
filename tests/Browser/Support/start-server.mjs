import { spawn, spawnSync } from 'node:child_process'
import { rmSync } from 'node:fs'
import { mkdir, rm, writeFile } from 'node:fs/promises'
import { dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { once } from 'node:events'

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../../..')
const port = Number.parseInt(process.argv[2], 10)

if (![8014, 8015].includes(port)) {
    throw new Error('Browser server port must be 8014 or 8015.')
}

const database = resolve(root, `storage/logs/browser-harness-${port}.sqlite`)
const environment = {
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
}

await mkdir(dirname(database), { recursive: true })
await rm(database, { force: true })
await writeFile(database, '')
process.on('exit', () => rmSync(database, { force: true }))

const bootstrap = spawnSync('php', ['tests/Browser/Support/bootstrap.php'], {
    cwd: root,
    env: environment,
    stdio: 'inherit',
})

if (bootstrap.status !== 0) {
    await rm(database, { force: true })
    throw new Error(`Browser database bootstrap failed with status ${bootstrap.status}.`)
}

const server = spawn('php', [
    'artisan',
    'serve',
    '--host=127.0.0.1',
    `--port=${port}`,
    '--no-reload',
], {
    cwd: root,
    env: environment,
    stdio: 'inherit',
})

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => server.kill(signal))
}

const [status, signal] = await once(server, 'exit')
await rm(database, { force: true })

if (signal) {
    process.kill(process.pid, signal)
} else {
    process.exitCode = status ?? 1
}
