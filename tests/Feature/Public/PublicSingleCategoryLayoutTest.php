<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use App\Models\Site;
use App\Support\Themes\PublicThemeContext;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class PublicSingleCategoryLayoutTest extends TestCase
{
    public function test_single_category_layout_has_a_distinct_focused_structure(): void
    {
        $site = new Site;
        $site->forceFill(['name' => 'Monitors Germany']);
        $theme = new PublicThemeContext(
            PublicThemeId::SingleCategory,
            PublicLayoutType::SingleCategory,
            ['header_variant' => 'focused'],
            ['locale-selector'],
        );
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.public-single-category', ['site' => $site, 'locale' => 'de-DE', 'theme' => $theme])

            @section('content')
                <p>Single shell content</p>
            @endsection
        BLADE, compact('site', 'theme'));

        self::assertStringContainsString('data-public-layout="single-category"', $html);
        self::assertStringContainsString('data-public-focused-hero', $html);
        self::assertStringContainsString('data-public-filter-slot', $html);
        self::assertStringContainsString('Single shell content', $html);
        self::assertStringNotContainsString('data-public-layout="multi-category"', $html);
        self::assertStringNotContainsString('data-public-category-slot', $html);
        self::assertStringNotContainsString('data-central-shell', $html);
        self::assertStringNotContainsString('data-site-shell', $html);
    }
}
