import assert from 'node:assert/strict'
import { spawn, spawnSync } from 'node:child_process'
import { once } from 'node:events'
import { existsSync, mkdtempSync, readFileSync, rmSync, writeFileSync } from 'node:fs'
import { tmpdir } from 'node:os'
import { delimiter, join, resolve } from 'node:path'
import test from 'node:test'

const root = resolve(import.meta.dirname, '../..')

function dockerFixture(context, delayed = false) {
    const directory = mkdtempSync(join(tmpdir(), 'cataloghub-visual-cleanup-'))
    context.after(() => rmSync(directory, { recursive: true, force: true }))
    const log = join(directory, 'calls.log')
    // Exercise the real runner and Playwright collection without starting a
    // real renderer. Only its Docker CLI is replaced in the child environment.
    writeFileSync(join(directory, 'docker'), `#!/usr/bin/env node
const fs = require('node:fs');
const command = process.argv[2];
fs.appendFileSync(process.env.CA015_VISUAL_DOCKER_LOG, command + '\\n');
if (command === 'run') setTimeout(() => process.stdout.write('owned-container\\n'), ${delayed ? 1000 : 0});
else if (command === 'port') process.stdout.write('127.0.0.1:12345\\n');
else if (command === 'logs') process.stdout.write('Listening on ws://127.0.0.1:3000/\\n');
else if (command === 'stop') { process.stderr.write('Docker stop failed'); process.exitCode = 42; }
else process.exitCode = 43;
`, { mode: 0o755 })
    return { log, options: { cwd: root, env: { ...process.env, PATH: directory + delimiter + process.env.PATH, CA015_VISUAL_DOCKER_LOG: log } } }
}

test('a successful real visual collection fails visibly when its renderer cleanup fails', (context) => {
    const { log, options } = dockerFixture(context)
    const result = spawnSync(process.execPath, ['tools/visual/run.mjs', '--list'], { ...options, encoding: 'utf8', timeout: 15_000 })
    assert.equal(result.status, 1, result.stderr)
    assert.match(result.stdout, /Total: \d+ tests/)
    assert.match(result.stderr, /Visual renderer cleanup failed:.*Docker stop failed/s)
    assert.deepEqual(readFileSync(log, 'utf8').trim().split('\n'), ['run', 'port', 'logs', 'stop'])
})

test('startup interruption stops the eventual container and preserves exit 130 when cleanup also fails', async (context) => {
    const { log, options } = dockerFixture(context, true)
    const child = spawn(process.execPath, ['tools/visual/run.mjs', '--list'], options)
    let stderr = ''
    child.stderr.on('data', (data) => { stderr += data })
    child.stdout.resume()
    const completion = once(child, 'exit', { signal: AbortSignal.timeout(10_000) })
    try {
        const deadline = Date.now() + 5000
        while (!existsSync(log) && Date.now() < deadline) await new Promise((done) => setTimeout(done, 25))
        assert.ok(existsSync(log), 'Runner must reach the in-flight Docker startup before cancellation')
        child.kill('SIGINT')
        const [status] = await completion
        assert.equal(status, 130, stderr)
        assert.match(stderr, /Visual run interrupted/)
        assert.match(stderr, /Visual renderer cleanup failed/)
        assert.deepEqual(readFileSync(log, 'utf8').trim().split('\n'), ['run', 'stop'])
    } finally {
        if (child.exitCode === null && child.signalCode === null) child.kill('SIGKILL')
    }
})
