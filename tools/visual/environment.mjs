import { readFileSync } from 'node:fs'

export const visualEnvironment = Object.freeze(JSON.parse(readFileSync(new URL('./environment.json', import.meta.url), 'utf8')))

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
