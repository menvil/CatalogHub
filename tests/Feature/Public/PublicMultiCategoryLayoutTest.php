<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use App\Models\Site;
use App\Support\Themes\PublicThemeContext;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class PublicMultiCategoryLayoutTest extends TestCase
{
    public function test_multi_category_layout_has_catalog_landmarks_without_admin_dependencies(): void
    {
        $this->withoutVite();

        $site = new Site;
        $site->forceFill(['name' => 'Tech Germany']);
        $theme = new PublicThemeContext(
            PublicThemeId::MultiCategory,
            PublicLayoutType::MultiCategory,
            ['header_variant' => 'catalog'],
            ['locale-selector'],
        );
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.public-multi-category', ['site' => $site, 'locale' => 'de-DE', 'theme' => $theme])

            @section('content')
                <p>Multi shell content</p>
            @endsection
        BLADE, compact('site', 'theme'));

        self::assertStringContainsString('data-public-layout="multi-category"', $html);
        self::assertStringContainsString('data-public-category-slot', $html);
        self::assertStringContainsString('data-public-search-slot', $html);
        self::assertStringContainsString('<header', $html);
        self::assertStringContainsString('<main id="main-content"', $html);
        self::assertStringContainsString('<footer', $html);
        self::assertStringContainsString('Multi shell content', $html);
        self::assertStringNotContainsString('data-public-layout="single-category"', $html);
        self::assertStringNotContainsString('data-central-shell', $html);
        self::assertStringNotContainsString('data-site-shell', $html);
        self::assertStringNotContainsString('central-admin-', $html);
        self::assertStringNotContainsString('site-admin-', $html);
    }
}
