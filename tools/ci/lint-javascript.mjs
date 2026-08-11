import { readdirSync, statSync } from 'node:fs'
import { spawnSync } from 'node:child_process'
import { resolve } from 'node:path'

const root = resolve(import.meta.dirname, '../..')
const targets = [
    'playwright.config.mjs',
    'vite.config.js',
    'resources/js',
    'tests/Browser',
    'tests/Frontend',
    'tests/Support',
    'tests/Visual/playwright',
]

function javascriptFiles(path) {
    if (!statSync(path).isDirectory()) {
        return /\.(?:m?js)$/.test(path) ? [path] : []
    }

    return readdirSync(path, { withFileTypes: true })
        .flatMap((entry) => javascriptFiles(resolve(path, entry.name)))
}

const files = targets.flatMap((target) => javascriptFiles(resolve(root, target))).sort()
let failed = false

for (const file of files) {
    const result = spawnSync(process.execPath, ['--check', file], { encoding: 'utf8' })

    if (result.status !== 0) {
        failed = true
        process.stderr.write(result.stderr)
    }
}

if (failed) {
    process.exitCode = 1
} else {
    console.log(`JavaScript syntax is valid in ${files.length} files.`)
}
