import assert from 'node:assert/strict'
import { existsSync, readFileSync, readdirSync } from 'node:fs'
import { dirname, resolve } from 'node:path'
import test from 'node:test'

const sourceRoot = resolve(import.meta.dirname, '../../resources/js')

function sourceFiles(directory) {
    return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
        const path = resolve(directory, entry.name)

        return entry.isDirectory() ? sourceFiles(path) : (entry.name.endsWith('.js') ? [path] : [])
    })
}

test('frontend relative imports resolve inside the checked-in module graph', () => {
    const files = sourceFiles(sourceRoot)
    let imports = 0

    for (const file of files) {
        const source = readFileSync(file, 'utf8')

        for (const match of source.matchAll(/\bfrom\s+['"](\.[^'"]+)['"]/g)) {
            imports++
            const imported = resolve(dirname(file), match[1])

            assert.ok(
                existsSync(imported) || existsSync(`${imported}.js`),
                `${file} imports missing module ${match[1]}`,
            )
        }
    }

    assert.ok(files.length > 0, 'No frontend modules were discovered.')
    assert.ok(imports > 0, 'No relative frontend imports were exercised.')
})
