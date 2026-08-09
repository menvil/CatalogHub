import { mkdtemp, rm, writeFile } from 'node:fs/promises'
import { tmpdir } from 'node:os'
import { join } from 'node:path'
import { spawn } from 'node:child_process'

const [chrome, url, capture, widthValue, heightValue, dom] = process.argv.slice(2)
const width = Number.parseInt(widthValue, 10)
const height = Number.parseInt(heightValue, 10)

if (!chrome || !url || !capture || !Number.isInteger(width) || !Number.isInteger(height)) {
    throw new Error('Usage: capture-chrome.mjs <chrome> <url> <capture> <width> <height> [dom]')
}

const profile = await mkdtemp(join(tmpdir(), 'cataloghub-chrome-'))
const browser = spawn(chrome, [
    '--headless=new',
    '--disable-gpu',
    '--hide-scrollbars',
    '--no-first-run',
    '--no-default-browser-check',
    '--remote-debugging-port=0',
    `--user-data-dir=${profile}`,
    'about:blank',
], { stdio: ['ignore', 'ignore', 'pipe'] })

try {
    const endpoint = await debuggingEndpoint(browser)
    const connection = await connect(endpoint)

    try {
        const { targetId } = await connection.send('Target.createTarget', { url: 'about:blank' })
        const { sessionId } = await connection.send('Target.attachToTarget', { targetId, flatten: true })

        await connection.send('Page.enable', {}, sessionId)
        await connection.send('Page.setLifecycleEventsEnabled', { enabled: true }, sessionId)
        await connection.send('Emulation.setDeviceMetricsOverride', {
            width,
            height,
            screenWidth: width,
            screenHeight: height,
            deviceScaleFactor: 1,
            mobile: false,
        }, sessionId)

        const loaded = connection.event('Page.loadEventFired', sessionId)
        const networkIdle = connection.event('Page.lifecycleEvent', sessionId, params => params.name === 'networkIdle')
        await connection.send('Page.navigate', { url }, sessionId)
        await loaded
        await networkIdle
        await connection.send('Runtime.evaluate', {
            expression: 'document.fonts.ready.then(() => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve))))',
            awaitPromise: true,
        }, sessionId)

        const screenshot = await connection.send('Page.captureScreenshot', {
            format: 'png',
            fromSurface: true,
            captureBeyondViewport: false,
        }, sessionId)
        await writeFile(capture, Buffer.from(screenshot.data, 'base64'))

        if (dom) {
            const result = await connection.send('Runtime.evaluate', {
                expression: 'document.documentElement.outerHTML',
                returnByValue: true,
            }, sessionId)
            await writeFile(dom, result.result.value)
        }

        connection.sendWithoutReply('Browser.close')
    } finally {
        connection.close()
    }
} finally {
    await stopBrowser(browser)
    await rm(profile, { recursive: true, force: true, maxRetries: 5, retryDelay: 100 })
}

async function stopBrowser(process) {
    if (process.exitCode !== null || process.signalCode !== null) {
        return
    }

    const exited = new Promise(resolve => process.once('exit', resolve))
    process.kill('SIGTERM')
    await Promise.race([exited, new Promise(resolve => setTimeout(resolve, 5_000))])

    if (process.exitCode === null && process.signalCode === null) {
        process.kill('SIGKILL')
        await exited
    }
}

async function debuggingEndpoint(process) {
    let output = ''

    return await new Promise((resolve, reject) => {
        const timeout = setTimeout(() => reject(new Error(`Chrome did not expose a debugging endpoint: ${output}`)), 10_000)

        process.stderr.setEncoding('utf8')
        process.stderr.on('data', chunk => {
            output += chunk
            const match = output.match(/DevTools listening on (ws:\/\/[^\s]+)/)

            if (match) {
                clearTimeout(timeout)
                resolve(match[1])
            }
        })
        process.once('exit', code => {
            clearTimeout(timeout)
            reject(new Error(`Chrome exited before exposing DevTools (${code}): ${output}`))
        })
    })
}

async function connect(endpoint) {
    const socket = new WebSocket(endpoint)
    let nextId = 1
    const pending = new Map()
    const listeners = []

    await new Promise((resolve, reject) => {
        socket.addEventListener('open', resolve, { once: true })
        socket.addEventListener('error', reject, { once: true })
    })

    const rejectTracked = failure => {
        const error = failure instanceof Error ? failure : new Error(String(failure))

        for (const request of pending.values()) {
            request.reject(error)
        }

        pending.clear()

        for (const listener of listeners.splice(0)) {
            listener.reject(error)
        }
    }

    socket.addEventListener('error', event => {
        rejectTracked(event.error instanceof Error ? event.error : new Error('Chrome DevTools WebSocket failed.'))
    })
    socket.addEventListener('close', event => {
        const reason = event.reason ? `: ${event.reason}` : ''
        rejectTracked(new Error(`Chrome DevTools WebSocket closed (${event.code})${reason}`))
    })

    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data)

        if (message.id && pending.has(message.id)) {
            const { resolve, reject } = pending.get(message.id)
            pending.delete(message.id)
            message.error ? reject(new Error(message.error.message)) : resolve(message.result ?? {})
            return
        }

        const index = listeners.findIndex(listener => (
            listener.method === message.method
            && listener.sessionId === message.sessionId
            && listener.predicate(message.params ?? {})
        ))

        if (index !== -1) {
            listeners.splice(index, 1)[0].resolve(message.params ?? {})
        }
    })

    return {
        send(method, params = {}, sessionId = undefined) {
            const id = nextId++
            const response = new Promise((resolve, reject) => pending.set(id, { resolve, reject }))
            socket.send(JSON.stringify({ id, method, params, ...(sessionId ? { sessionId } : {}) }))

            return response
        },
        sendWithoutReply(method, params = {}, sessionId = undefined) {
            socket.send(JSON.stringify({ id: nextId++, method, params, ...(sessionId ? { sessionId } : {}) }))
        },
        event(method, sessionId, predicate = () => true) {
            return new Promise((resolve, reject) => listeners.push({ method, sessionId, predicate, resolve, reject }))
        },
        close() {
            socket.close()
        },
    }
}
