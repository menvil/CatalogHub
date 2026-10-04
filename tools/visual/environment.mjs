import { readFileSync } from 'node:fs'
import { resolve, sep } from 'node:path'

export const visualEnvironment = Object.freeze(JSON.parse(readFileSync(new URL('./environment.json', import.meta.url), 'utf8')))

// Fail closed for unscoped or mixed-project runs. File-scoped Browser runs do
// not select the Visual project and can use the normal native browser.
export function selectsOnlyBrowser(args, root) {
    const projects = []
    const files = []
    const switches = new Set(['--list', '--headed', '--quiet', '--no-deps', '--pass-with-no-tests', '--fail-on-flaky-tests', '--forbid-only', '--fully-parallel', '--ignore-snapshots', '--last-failed', '--ui', '-x'])
    for (let index = args[0] === 'test' ? 1 : 0; index < args.length; index++) {
        const argument = args[index]
        if (argument.startsWith('--project=')) projects.push(argument.slice(10))
        else if (argument === '--project') {
            while (args[index + 1] && !args[index + 1].startsWith('-')) projects.push(args[++index])
        } else if (argument.startsWith('-')) {
            if (!argument.includes('=') && !switches.has(argument)) index++
        } else files.push(argument)
    }
    if (projects.length) return projects.every((project) => project === 'browser')
    const browserDirectory = resolve(root, 'tests/Browser')
    return files.length > 0 && files.every((file) => {
        const path = resolve(root, file.replace(/:\d+(?::\d+)?$/, ''))
        return path === browserDirectory || path.startsWith(browserDirectory + sep)
    })
}

export function validateVisualRun(version, args, isCI) {
    if (version !== visualEnvironment.playwrightVersion) {
        throw new Error('Playwright and the pinned visual image must have the same version. Update tools/visual/environment.json and review references together.')
    }
    if (args.some((arg) => arg.startsWith('--project') || arg.startsWith('--config') || arg === '-c')) {
        throw new Error('The visual runner owns its project and config. Use npm run test:visual -- <filters>.')
    }
    if (isCI && args.some((arg) => arg.startsWith('--update-snapshots') || arg === '-u')) {
        throw new Error('CI may compare visual references but must never update them.')
    }
}
