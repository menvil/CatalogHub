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

    return execFileSync('php', ['artisan', 'tinker', '--execute', command], {
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
        encoding: 'utf8',
    })
}

export function clearSourceTagline() {
    updateBrandTranslationLocales([
        "App\\Models\\Translations\\BrandTranslation::query()->where('brand_id', 24)->where('locale', 'en-US')->update(['tagline' => null]);",
    ])
}

export function keepOnlyTargetLocale() {
    updateBrandTranslationLocales([
        "App\\Models\\Locale::query()->where('code', '!=', 'de-DE')->update(['is_active' => false, 'is_default' => false]);",
    ])
}

export function addWorkspaceLanguageOptions() {
    updateBrandTranslationLocales([
        "$languages = ['bg-BG' => 'Bulgarian', 'es-ES' => 'Spanish', 'it-IT' => 'Italian', 'pt-PT' => 'Portuguese', 'nl-NL' => 'Dutch', 'pl-PL' => 'Polish', 'cs-CZ' => 'Czech', 'sv-SE' => 'Swedish', 'da-DK' => 'Danish', 'fi-FI' => 'Finnish', 'el-GR' => 'Greek', 'tr-TR' => 'Turkish', 'ja-JP' => 'Japanese', 'ko-KR' => 'Korean', 'zh-CN' => 'Chinese', 'uk-UA' => 'Ukrainian'];",
        "foreach ($languages as $code => $name) { [$language, $region] = explode('-', $code); $locale = App\\Models\\Locale::query()->firstOrNew(['code' => $code]); $locale->forceFill(['name' => $name, 'language_code' => $language, 'region_code' => $region, 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'position' => 20])->saveOrFail(); }",
    ])
}

export function workspacePersistence() {
    return JSON.parse(updateBrandTranslationLocales([
        "echo json_encode(['brand' => App\\Models\\CentralCatalog\\CentralBrand::query()->findOrFail(24)->getRawOriginal(), 'rows' => App\\Models\\Translations\\BrandTranslation::query()->where('brand_id', 24)->orderBy('id')->get()->map(fn ($row) => $row->getRawOriginal()), 'locales' => App\\Models\\Locale::query()->orderBy('id')->get()->map(fn ($locale) => $locale->getRawOriginal()), 'audit' => App\\Models\\AuditLogEntry::query()->count()]);",
    ]).trim())
}
