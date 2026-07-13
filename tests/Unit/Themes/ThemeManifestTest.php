<?php

namespace Tests\Unit\Themes;

use App\Domains\Themes\ValueObjects\ThemeManifest;
use App\Exceptions\Themes\InvalidThemeManifestException;
use PHPUnit\Framework\TestCase;

class ThemeManifestTest extends TestCase
{
    public function test_valid_manifest_exposes_capabilities_layouts_and_array_form(): void
    {
        $manifest = ThemeManifest::fromArray([
            'code' => 'catalog_clean',
            'name' => 'Catalog Clean',
            'supports' => ['hero_search', 'price_block'],
            'layouts' => ['home' => 'home-clean', 'product' => 'product-default'],
            'version' => '1.0.0',
            'preview' => 'themes/catalog-clean.png',
        ]);

        $this->assertSame('catalog_clean', $manifest->code);
        $this->assertSame('Catalog Clean', $manifest->name);
        $this->assertTrue($manifest->supports('hero_search'));
        $this->assertFalse($manifest->supports('poll_block'));
        $this->assertSame('home-clean', $manifest->layoutFor('home'));
        $this->assertNull($manifest->layoutFor('search'));
        $this->assertSame('1.0.0', $manifest->toArray()['version']);
    }

    public function test_manifest_without_code_is_rejected(): void
    {
        $this->expectException(InvalidThemeManifestException::class);
        $this->expectExceptionMessage('code is required');

        ThemeManifest::fromArray(['name' => 'Missing Code', 'layouts' => ['home' => 'home-clean']]);
    }

    public function test_manifest_without_layouts_is_rejected(): void
    {
        $this->expectException(InvalidThemeManifestException::class);
        $this->expectExceptionMessage('layouts must be a non-empty');

        ThemeManifest::fromArray(['code' => 'no_layouts', 'name' => 'No Layouts']);
    }

    public function test_non_list_supports_are_rejected(): void
    {
        $this->expectException(InvalidThemeManifestException::class);
        $this->expectExceptionMessage('supports must be a list');

        ThemeManifest::fromArray([
            'code' => 'invalid_supports',
            'name' => 'Invalid Supports',
            'supports' => ['hero_search' => true],
            'layouts' => ['home' => 'home-clean'],
        ]);
    }

    public function test_non_array_layouts_are_rejected(): void
    {
        $this->expectException(InvalidThemeManifestException::class);

        ThemeManifest::fromArray([
            'code' => 'invalid_layouts',
            'name' => 'Invalid Layouts',
            'layouts' => 'home-clean',
        ]);
    }

    public function test_unknown_layout_page_type_is_rejected(): void
    {
        $this->expectException(InvalidThemeManifestException::class);
        $this->expectExceptionMessage('Unknown theme layout page type');

        ThemeManifest::fromArray([
            'code' => 'unknown_page',
            'name' => 'Unknown Page',
            'layouts' => ['landing_campaign' => 'campaign-layout'],
        ]);
    }
}
