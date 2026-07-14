<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Services\ThemeRegistry;
use App\Enums\ThemeStatus;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThemeRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_lists_only_active_themes_and_finds_by_code(): void
    {
        $active = Theme::factory()->create(['code' => 'catalog_clean', 'name' => 'Catalog Clean', 'status' => ThemeStatus::Active]);
        Theme::factory()->create(['status' => ThemeStatus::Draft]);
        Theme::factory()->create(['status' => ThemeStatus::Archived]);
        $registry = app(ThemeRegistry::class);

        $this->assertSame([$active->id], $registry->activeThemes()->pluck('id')->all());
        $this->assertTrue($registry->findByCode('catalog_clean')?->is($active));
        $this->assertNull($registry->findByCode('missing_theme'));
    }

    public function test_registry_resolves_manifest_and_capability_support(): void
    {
        $theme = Theme::factory()->create(['code' => 'catalog_clean']);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => [
                'code' => 'catalog_clean',
                'name' => 'Catalog Clean',
                'supports' => ['hero_search', 'price_block'],
                'layouts' => ['home' => 'home-clean'],
            ],
            'supports_json' => ['hero_search', 'price_block'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);
        $registry = app(ThemeRegistry::class);

        $this->assertSame('home-clean', $registry->manifestFor($theme)->layoutFor('home'));
        $this->assertTrue($registry->themeSupports($theme, 'hero_search'));
        $this->assertFalse($registry->themeSupports($theme, 'poll_block'));
    }

    public function test_theme_without_manifest_is_rejected_explicitly(): void
    {
        $this->expectException(InvalidThemeManifestException::class);
        $this->expectExceptionMessage('does not have a manifest');

        app(ThemeRegistry::class)->manifestFor(Theme::factory()->create());
    }

    public function test_active_theme_manifests_are_eager_loaded_and_reused(): void
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => ['code' => $theme->code, 'name' => $theme->name, 'supports' => [], 'layouts' => ['home' => 'home-clean']],
            'supports_json' => [],
            'layouts_json' => ['home' => 'home-clean'],
        ]);
        $registry = app(ThemeRegistry::class);
        $loadedTheme = $registry->activeThemes()->firstOrFail();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $registry->manifestFor($loadedTheme);
        $registry->manifestFor($loadedTheme);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertSame([], $queries);
    }
}
