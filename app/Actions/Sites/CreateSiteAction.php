<?php

namespace App\Actions\Sites;

use App\Enums\CentralCategoryStatus;
use App\Enums\SiteMode;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteFeature;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class CreateSiteAction
{
    /** @param array<string, mixed> $data */
    public function handle(array $data): Site
    {
        $market = Market::query()->find($data['market_id'] ?? null);

        if (! $market?->isActive()) {
            throw ValidationException::withMessages(['market_id' => 'The selected market must be active.']);
        }

        $locales = array_values(array_unique($data['locales'] ?? []));

        if ($locales === []) {
            throw ValidationException::withMessages(['locales' => 'At least one locale must be enabled.']);
        }

        if (! in_array($data['default_locale'] ?? null, $locales, true)) {
            throw ValidationException::withMessages(['default_locale' => 'The default locale must be enabled for the site.']);
        }

        $activeLocaleCount = Locale::query()->active()->whereIn('code', $locales)->count();

        if ($activeLocaleCount !== count($locales)) {
            throw ValidationException::withMessages(['locales' => 'Only active locales can be enabled for a site.']);
        }

        $data['locales'] = $locales;

        $features = $data['features'] ?? [];

        if (! is_array($features) || array_diff(array_keys($features), SiteFeature::KEYS) !== []) {
            throw ValidationException::withMessages(['features' => 'Only supported site features can be configured.']);
        }

        Validator::make(['features' => $features], [
            'features.*' => ['required', 'boolean:strict'],
        ])->validate();

        $data['features'] = $features;

        $mode = $data['mode'] ?? null;

        if (! is_string($mode) || SiteMode::tryFrom($mode) === null) {
            throw ValidationException::withMessages(['mode' => 'The selected site mode is invalid.']);
        }

        $data['mode'] = $mode;

        $categories = array_values(array_unique(array_map('intval', $data['categories'] ?? [])));
        $categoryCount = count($categories);

        if ($mode === SiteMode::SingleCategory->value && $categoryCount !== 1) {
            throw ValidationException::withMessages(['categories' => 'Single-category sites require exactly one enabled category.']);
        }

        if ($mode === SiteMode::MultiCategory->value && $categoryCount < 1) {
            throw ValidationException::withMessages(['categories' => 'Multi-category sites require at least one enabled category.']);
        }

        $activeCategoryCount = CentralCategory::query()
            ->whereKey($categories)
            ->where('status', CentralCategoryStatus::Active)
            ->count();

        if ($activeCategoryCount !== $categoryCount) {
            throw ValidationException::withMessages(['categories' => 'Only active categories can be enabled for a site.']);
        }

        $data['categories'] = $categories;

        return DB::transaction(function () use ($data): Site {
            $site = Site::query()->create([
                'market_id' => $data['market_id'], 'code' => $data['code'], 'name' => $data['name'], 'domain' => $data['domain'] ?? null,
                'mode' => $data['mode'], 'default_locale' => $data['default_locale'], 'status' => $data['status'] ?? 'draft', 'settings_json' => $data['settings_json'] ?? [],
            ]);
            foreach ($data['locales'] as $position => $locale) {
                DB::table('site_locales')->insert(['site_id' => $site->id, 'locale_code' => $locale, 'is_default' => $locale === $data['default_locale'], 'is_enabled' => true, 'position' => $position, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($data['categories'] as $position => $categoryId) {
                DB::table('site_categories')->insert(['site_id' => $site->id, 'central_category_id' => $categoryId, 'is_enabled' => true, 'position' => $position, 'created_at' => now(), 'updated_at' => now()]);
            }
            foreach ($data['features'] as $feature => $enabled) {
                DB::table('site_features')->insert(['site_id' => $site->id, 'feature_key' => $feature, 'is_enabled' => $enabled, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $site;
        });
    }
}
