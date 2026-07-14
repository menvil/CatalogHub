<?php

namespace Tests\Unit\Themes;

use App\Domains\Themes\ThemeLayoutResolver;
use App\Enums\ThemeStatus;
use App\Exceptions\Themes\CannotResolveThemeLayoutException;
use App\Models\LayoutTemplate;
use App\Models\Site;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeLayoutResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_configured_theme_templates_and_default_fallbacks(): void
    {
        $theme = Theme::factory()->create(['status' => ThemeStatus::Active]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $theme->id,
            'manifest_json' => [
                'code' => $theme->code,
                'name' => $theme->name,
                'supports' => [],
                'layouts' => ['home' => 'editorial-home', 'category' => 'category-grid'],
            ],
        ]);
        LayoutTemplate::query()->create([
            'theme_id' => $theme->id,
            'page_type' => 'home',
            'code' => 'editorial-home',
            'name' => 'Editorial Home',
            'view_path' => 'themes.demo.home',
            'status' => 'active',
        ]);
        LayoutTemplate::query()->create([
            'theme_id' => $theme->id,
            'page_type' => 'category',
            'code' => 'category-grid',
            'name' => 'Category Grid',
            'view_path' => 'themes.demo.category',
            'status' => 'active',
        ]);
        $site = Site::factory()->create(['theme_id' => $theme->id]);
        $resolver = app(ThemeLayoutResolver::class);

        $this->assertSame('themes.demo.home', $resolver->resolve($site, 'home'));
        $this->assertSame('themes.demo.category', $resolver->resolve($site, 'category'));
        $this->assertSame('public.pages.product', $resolver->resolve($site, 'product'));
        $this->assertSame('public.pages.product', $resolver->resolve($site, 'product', 'missing-layout'));
    }

    public function test_it_rejects_unsupported_page_types_explicitly(): void
    {
        $this->expectException(CannotResolveThemeLayoutException::class);

        app(ThemeLayoutResolver::class)->resolve(Site::factory()->create(), 'checkout');
    }
}
