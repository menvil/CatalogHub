import assert from 'node:assert/strict'
import test from 'node:test'
import { validateVisualRun, visualEnvironment } from '../../tools/visual/environment.mjs'

test('visual capture pins the Linux architecture and immutable browser/fonts image', () => {
    assert.equal(visualEnvironment.platform, 'linux/amd64')
    assert.match(visualEnvironment.image, /^mcr\.microsoft\.com\/playwright@sha256:[a-f0-9]{64}$/)
    assert.throws(() => validateVisualRun('0.0.0', [], false), /same version/)
    assert.doesNotThrow(() => validateVisualRun(visualEnvironment.playwrightVersion, ['--grep', 'CA-015'], true))
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
