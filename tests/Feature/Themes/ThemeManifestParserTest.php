<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Services\ThemeManifestParser;
use App\Exceptions\Themes\InvalidThemeManifestException;
use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeManifestParserTest extends TestCase
{
    use RefreshDatabase;

    public function test_parser_accepts_json_and_stores_normalized_capabilities(): void
    {
        $theme = Theme::factory()->create(['code' => 'catalog_clean']);
        $manifest = json_encode([
            'code' => 'catalog_clean',
            'name' => 'Catalog Clean',
            'supports' => ['hero_search', 'price_block'],
            'layouts' => ['home' => 'home-clean'],
            'schema_version' => '1',
        ], JSON_THROW_ON_ERROR);

        $parsed = app(ThemeManifestParser::class)->parseAndStore($theme, $manifest);
        $record = $theme->manifest()->firstOrFail();

        $this->assertTrue($parsed->supports('hero_search'));
        $this->assertSame(['hero_search', 'price_block'], $record->supports_json);
        $this->assertSame(['home' => 'home-clean'], $record->layouts_json);
        $this->assertSame('1', $record->schema_version);
        $this->assertNotNull($record->validated_at);
        $this->assertNull($record->validation_errors_json);
    }

    public function test_parser_updates_existing_manifest_for_same_theme(): void
    {
        $theme = Theme::factory()->create(['code' => 'catalog_clean']);
        $parser = app(ThemeManifestParser::class);
        $base = [
            'code' => 'catalog_clean',
            'name' => 'Catalog Clean',
            'supports' => ['hero_search'],
            'layouts' => ['home' => 'home-clean'],
        ];

        $parser->parseAndStore($theme, $base);
        $firstId = $theme->manifest()->firstOrFail()->id;
        $base['supports'][] = 'price_block';
        $parser->parseAndStore($theme, $base);

        $this->assertDatabaseCount('theme_manifests', 1);
        $this->assertSame($firstId, $theme->manifest()->firstOrFail()->id);
        $this->assertSame(['hero_search', 'price_block'], $theme->manifest()->firstOrFail()->supports_json);
    }

    public function test_invalid_json_is_rejected_without_persistence(): void
    {
        $theme = Theme::factory()->create(['code' => 'catalog_clean']);

        try {
            app(ThemeManifestParser::class)->parseAndStore($theme, '{invalid json');
            $this->fail('Invalid manifest JSON was accepted.');
        } catch (InvalidThemeManifestException $exception) {
            $this->assertStringContainsString('JSON is invalid', $exception->getMessage());
        }

        $this->assertDatabaseMissing('theme_manifests', ['theme_id' => $theme->id]);
    }

    public function test_manifest_code_must_match_theme_code(): void
    {
        $theme = Theme::factory()->create(['code' => 'catalog_clean']);

        $this->expectException(InvalidThemeManifestException::class);
        $this->expectExceptionMessage('code must match');

        app(ThemeManifestParser::class)->parseAndStore($theme, [
            'code' => 'different_theme',
            'name' => 'Different Theme',
            'layouts' => ['home' => 'home-clean'],
        ]);
    }
}
