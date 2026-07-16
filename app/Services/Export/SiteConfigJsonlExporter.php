<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteFeature;
use App\Models\SiteHomeBlock;
use App\Models\SiteLocale;
use Generator;

final class SiteConfigJsonlExporter implements JsonlExporter
{
    public function __construct(
        private readonly JsonlStreamWriter $writer,
        private readonly SafeConfigSanitizer $sanitizer,
    ) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        return $this->writer->write($snapshot, 'site_config', $this->rows());
    }

    /** @return Generator<int, array<string, mixed>> */
    private function rows(): Generator
    {
        foreach (Market::query()->orderBy('id')->cursor() as $market) {
            yield [
                'entity_type' => 'market',
                'id' => $market->getKey(),
                'code' => $market->code,
                'name' => $market->name,
                'country_code' => $market->country_code,
                'currency_code' => $market->currency_code,
                'default_locale' => $market->default_locale,
                'timezone' => $market->timezone,
                'status' => $market->status->value,
                'config' => $this->sanitizer->sanitize($market->config_json),
                'updated_at' => $market->updated_at?->toISOString(),
            ];
        }

        foreach (Site::query()->withTrashed()->orderBy('id')->cursor() as $site) {
            yield [
                'entity_type' => 'site',
                'id' => $site->getKey(),
                'market_id' => $site->market_id,
                'theme_id' => $site->theme_id,
                'code' => $site->code,
                'name' => $site->name,
                'domain' => $site->domain,
                'mode' => $site->mode->value,
                'default_locale' => $site->default_locale,
                'status' => $site->status->value,
                'settings' => $this->sanitizer->sanitize($site->settings_json),
                'deleted_at' => $site->deleted_at?->toISOString(),
                'updated_at' => $site->updated_at?->toISOString(),
            ];
        }

        foreach (SiteLocale::query()->orderBy('id')->cursor() as $locale) {
            yield [
                'entity_type' => 'site_locale',
                'id' => $locale->getKey(),
                'site_id' => $locale->site_id,
                'locale' => $locale->locale_code,
                'is_default' => $locale->is_default,
                'is_enabled' => $locale->is_enabled,
                'position' => $locale->position,
            ];
        }

        foreach (SiteCategory::query()->orderBy('id')->cursor() as $category) {
            yield [
                'entity_type' => 'site_category',
                'id' => $category->getKey(),
                'site_id' => $category->site_id,
                'category_id' => $category->central_category_id,
                'is_enabled' => $category->is_enabled,
                'position' => $category->position,
                'local_status' => $category->local_status,
                'settings' => $this->sanitizer->sanitize($category->settings_json),
            ];
        }

        foreach (SiteFeature::query()->orderBy('id')->cursor() as $feature) {
            yield [
                'entity_type' => 'site_feature',
                'id' => $feature->getKey(),
                'site_id' => $feature->site_id,
                'feature_key' => $feature->feature_key,
                'is_enabled' => $feature->is_enabled,
                'config' => $this->sanitizer->sanitize($feature->config_json),
            ];
        }

        foreach (SiteHomeBlock::query()->orderBy('id')->cursor() as $block) {
            yield [
                'entity_type' => 'site_home_block',
                'id' => $block->getKey(),
                'site_id' => $block->site_id,
                'block_code' => $block->block_code,
                'position' => $block->position,
                'enabled' => $block->enabled,
                'config' => $this->sanitizer->sanitize($block->config_json),
                'visibility' => $this->sanitizer->sanitize($block->visibility_json),
            ];
        }
    }
}
