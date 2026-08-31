import { execFileSync } from 'node:child_process'
import { resolve } from 'node:path'

export function activateRtlBrandTranslationLocale() {
    updateBrandTranslationLocales([
        "App\\Models\\Locale::query()->where('code', 'en-DE')->update(['is_active' => false, 'is_default' => false]);",
        "App\\Models\\Locale::query()->where('code', 'ar-SA')->update(['is_active' => true]);",
    ])
}

export function restoreDefaultBrandTranslationLocales() {
    updateBrandTranslationLocales([
        "App\\Models\\Locale::query()->where('code', 'en-DE')->update(['is_active' => true]);",
        "App\\Models\\Locale::query()->where('code', 'ar-SA')->update(['is_active' => false, 'is_default' => false]);",
    ])
}

function updateBrandTranslationLocales(statements) {
    const port = Number.parseInt(process.env.CATALOGHUB_BROWSER_PORT ?? '', 10)

    if (![8014, 8015].includes(port)) {
        throw new Error('The deterministic RTL fixture requires the Browser harness port.')
    }

    const root = resolve(import.meta.dirname, '../../..')
    const database = resolve(root, `storage/logs/browser-harness-${port}.sqlite`)
    const command = statements.join(' ')

    execFileSync('php', ['artisan', 'tinker', '--execute', command], {
        cwd: root,
        env: {
            ...process.env,
            APP_ENV: 'testing',
            APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
            DB_CONNECTION: 'sqlite',
            DB_DATABASE: database,
            DB_URL: '',
            CACHE_STORE: 'array',
            QUEUE_CONNECTION: 'sync',
            SESSION_DRIVER: 'file',
        },
        stdio: 'pipe',
    })
}
