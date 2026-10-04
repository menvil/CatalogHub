import assert from 'node:assert/strict'
import { EventEmitter } from 'node:events'
import test from 'node:test'
import { VisualRunLifecycle } from '../../tools/visual/lifecycle.mjs'

test('a repeated interrupt reaches the pending cleanup CLI and disposal removes its listeners', async () => {
    const signals = new EventEmitter()
    const killed = []
    let completeStop
    const lifecycle = new VisualRunLifecycle(() => new Promise((done) => { completeStop = done }), signals)
    lifecycle.container = 'owned-container'
    lifecycle.cleanupChild = { exitCode: null, signalCode: null, kill: (signal) => killed.push(signal) }
    const disposing = lifecycle.dispose()
    signals.emit('SIGINT')
    signals.emit('SIGINT')
    assert.deepEqual(killed, ['SIGTERM', 'SIGKILL'])
    completeStop()
    await disposing
    assert.equal(signals.listenerCount('SIGINT'), 0)
    assert.equal(signals.listenerCount('SIGTERM'), 0)
})

test('an uninterrupted lifecycle stays running and disposes its container and both listeners', async () => {
    const signals = new EventEmitter()
    const stopped = []
    const lifecycle = new VisualRunLifecycle(async (id) => stopped.push(id), signals)
    lifecycle.container = 'owned-container'
    assert.doesNotThrow(() => lifecycle.assertRunning())
    await lifecycle.dispose()
    assert.deepEqual(stopped, ['owned-container'])
    assert.equal(signals.listenerCount('SIGINT'), 0)
    assert.equal(signals.listenerCount('SIGTERM'), 0)
})

test('interrupts skip signals to an exited child while still disposing the owned container', async () => {
    const signals = new EventEmitter()
    const killed = []
    const stopped = []
    const lifecycle = new VisualRunLifecycle(async (id) => stopped.push(id), signals)
    lifecycle.container = 'owned-container'
    lifecycle.child = { exitCode: 0, signalCode: null, kill: (signal) => killed.push(signal) }
    signals.emit('SIGINT')
    signals.emit('SIGINT')
    await lifecycle.dispose()
    assert.deepEqual(killed, [])
    assert.deepEqual(stopped, ['owned-container'])
})

test('a stalled startup CLI receives termination and bounded forced escalation before Playwright exists', async () => {
    const signals = new EventEmitter()
    const killed = []
    const stopped = []
    const lifecycle = new VisualRunLifecycle(async (id) => stopped.push(id), signals, 10)
    lifecycle.container = 'owned-container'
    lifecycle.startupChild = { exitCode: null, signalCode: null, kill: (signal) => killed.push(signal) }
    signals.emit('SIGTERM')
    await new Promise((done) => setTimeout(done, 25))
    assert.deepEqual(killed, ['SIGTERM', 'SIGKILL'])
    assert.throws(() => lifecycle.assertRunning(), /interrupted/)
    assert.deepEqual(stopped, [])
    await lifecycle.dispose()
    assert.deepEqual(stopped, ['owned-container'])
})

test('cancellation before docker run returns prevents continuation and cleans up the eventual container', async () => {
    const signals = new EventEmitter()
    const stopped = []
    const lifecycle = new VisualRunLifecycle(async (id) => stopped.push(id), signals)
    signals.emit('SIGINT')
    // docker run was already in flight: its ID only becomes known afterward.
    lifecycle.container = 'owned-container'
    assert.throws(() => lifecycle.assertRunning(), /interrupted/)
    await lifecycle.dispose()
    assert.deepEqual(stopped, ['owned-container'])
    assert.equal(signals.listenerCount('SIGINT'), 0)
    assert.equal(signals.listenerCount('SIGTERM'), 0)
})

test('cancellation during startup awaits cleanup already requested by the signal handler', async () => {
    const signals = new EventEmitter()
    let completeStop
    const lifecycle = new VisualRunLifecycle(() => new Promise((done) => { completeStop = done }), signals)
    lifecycle.container = 'owned-container'
    signals.emit('SIGTERM')
    assert.throws(() => lifecycle.assertRunning(), /interrupted/)
    let disposed = false
    const disposing = lifecycle.dispose().then(() => { disposed = true })
    await Promise.resolve()
    assert.equal(disposed, false)
    completeStop()
    await disposing
    assert.equal(disposed, true)
})

test('a repeated interrupt forces a hung Playwright child down and stops only the owned container', async () => {
    const signals = new EventEmitter()
    const killed = []
    const stopped = []
    const lifecycle = new VisualRunLifecycle(async (id) => stopped.push(id), signals)
    lifecycle.container = 'owned-container'
    lifecycle.child = { exitCode: null, signalCode: null, kill: (signal) => killed.push(signal) }
    signals.emit('SIGINT')
    assert.deepEqual(killed, ['SIGINT'])
    assert.deepEqual(stopped, [])
    signals.emit('SIGINT')
    await lifecycle.dispose()
    assert.deepEqual(killed, ['SIGINT', 'SIGKILL'])
    assert.deepEqual(stopped, ['owned-container'])
})

test('an ignored first interrupt escalates after the bounded graceful shutdown period', async () => {
    const signals = new EventEmitter()
    const killed = []
    const lifecycle = new VisualRunLifecycle(async () => {}, signals, 10)
    lifecycle.child = { exitCode: null, signalCode: null, kill: (signal) => killed.push(signal) }
    signals.emit('SIGTERM')
    await new Promise((done) => setTimeout(done, 25))
    await lifecycle.dispose()
    assert.deepEqual(killed, ['SIGINT', 'SIGKILL'])
})

test('cleanup failures requested by a signal are surfaced on disposal without an unhandled rejection', async () => {
    const signals = new EventEmitter()
    const failure = new Error('Docker stop failed')
    const lifecycle = new VisualRunLifecycle(async () => { throw failure }, signals)
    lifecycle.container = 'owned-container'
    signals.emit('SIGTERM')
    await assert.rejects(lifecycle.dispose(), (error) => error === failure)
    assert.equal(signals.listenerCount('SIGTERM'), 0)
})
