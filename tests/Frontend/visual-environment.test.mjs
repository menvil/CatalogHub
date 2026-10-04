import assert from 'node:assert/strict'
import test from 'node:test'
import { spawnSync } from 'node:child_process'
import { selectsOnlyBrowser, validateVisualRun, visualEnvironment } from '../../tools/visual/environment.mjs'

test('visual capture pins the Linux architecture and immutable browser/fonts image', () => {
    assert.equal(visualEnvironment.platform, 'linux/amd64')
    assert.match(visualEnvironment.image, /^mcr\.microsoft\.com\/playwright@sha256:[a-f0-9]{64}$/)
    assert.throws(() => validateVisualRun('0.0.0', [], false), /same version/)
    assert.doesNotThrow(() => validateVisualRun(visualEnvironment.playwrightVersion, ['--grep', 'CA-015'], true))
})

test('native Browser selection is allowed while direct Visual runs and forged worker flags are rejected', () => {
    const environment = { ...process.env, CATALOGHUB_BROWSER_PORT: '8015' }
    delete environment.CATALOGHUB_VISUAL_IMAGE
    delete environment.PW_TEST_CONNECT_WS_ENDPOINT
    delete environment.TEST_WORKER_INDEX
    const args = ['--input-type=module', '-e', "process.argv.splice(1, 0, 'config-check.mjs'); await import('./playwright.config.mjs')"]
    const nativeVisual = spawnSync(process.execPath, args, { env: environment, encoding: 'utf8' })
    assert.notEqual(nativeVisual.status, 0)
    assert.match(nativeVisual.stderr, /pinned Linux renderer/)
    const nativeBrowser = spawnSync(process.execPath, [...args, '--', '--project=browser'], { env: environment, encoding: 'utf8' })
    assert.equal(nativeBrowser.status, 0, nativeBrowser.stderr)
    const worker = spawnSync(process.execPath, args, { env: { ...environment, TEST_WORKER_INDEX: '0' }, encoding: 'utf8' })
    assert.notEqual(worker.status, 0)
    assert.match(worker.stderr, /pinned Linux renderer/)
    const fileScoped = spawnSync(process.execPath, [...args, '--', 'tests/Browser/Acceptance/brand-translations.spec.mjs', '--list'], { env: environment, encoding: 'utf8' })
    assert.equal(fileScoped.status, 0, fileScoped.stderr)
})

test('native selection allows Browser files and filters while rejecting Visual, mixed, wildcard and unscoped runs', () => {
    const root = process.cwd()
    for (const args of [
        ['test', 'tests/Browser/Acceptance/brand-translations.spec.mjs'],
        ['test', 'tests/Browser', '--list'],
        ['test', '--grep', 'CA-015', 'tests/Browser/Acceptance/brand-translations.spec.mjs:234'],
        ['test', '--project', 'browser', '--list'],
        ['test', '--project=browser'],
    ]) assert.equal(selectsOnlyBrowser(args, root), true, args.join(' '))
    for (const args of [
        ['test'], ['test', '--list'], ['test', '--project=*'],
        ['test', '--project', 'browser', 'visual'],
        ['test', 'tests/Visual/playwright/brands.visual.spec.mjs'],
        ['test', 'tests/Browser', 'tests/Visual'],
        ['test', '--grep', 'tests/Browser'],
        ['test', 'tests/Browser/../Visual'],
        ['test', 'tests/Browser/Acceptance/.*|tests/Visual'],
        ['test', 'tests/Browser/not-a-real-test.spec.mjs'],
    ]) assert.equal(selectsOnlyBrowser(args, root), false, args.join(' '))
})

test('visual runner permits explicit local reference capture but never CI updates or config overrides', () => {
    const version = visualEnvironment.playwrightVersion
    assert.doesNotThrow(() => validateVisualRun(version, ['--update-snapshots=all', '--grep', 'CA-015'], false))
    for (const flag of ['--update-snapshots', '--update-snapshots=all', '-u']) {
        assert.throws(() => validateVisualRun(version, [flag], true), /must never update/)
    }
    for (const flag of ['--project=browser', '--config=other.mjs', '-c']) {
        assert.throws(() => validateVisualRun(version, [flag], false), /owns its project/)
    }
})
