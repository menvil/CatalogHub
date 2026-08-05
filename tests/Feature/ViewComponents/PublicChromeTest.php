<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use App\Models\Site;
use App\Services\PublicSite\PublicLocaleNavigation;
use App\Support\Themes\PublicThemeContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use LogicException;
use Tests\TestCase;

final class PublicChromeTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_navigation_contains_only_enabled_locales_and_uses_canonical_site_urls(): void
    {
        $site = Site::factory()->active()->withRuntimeContext(['de-DE', 'en-DE'])->create([
            'domain' => 'public-chrome.test',
            'name' => 'A deliberately long public catalogue identity',
        ]);
        $site->locales()->where('locale_code', 'en-DE')->update(['is_enabled' => false]);
        $site->load('locales.locale');

        $options = app(PublicLocaleNavigation::class)->forHome($site, 'de-DE');
        $html = Blade::render(
            '<x-public.header :site="$site" :locale-options="$options" :navigation="[]" />',
            compact('site', 'options'),
        );

        $this->assertCount(1, $options);
        $this->assertStringContainsString('A deliberately long public catalogue identity', $html);
        $this->assertStringContainsString('de-DE', $html);
        $this->assertStringContainsString('lang="de-DE"', $html);
        $this->assertStringNotContainsString('en-DE', $html);
        $this->assertStringNotContainsString('href="#"', $html);
        $this->assertStringContainsString('Search unavailable', $html);
    }

    public function test_both_public_layouts_reuse_shared_chrome_with_multiple_locales(): void
    {
        $this->withoutVite();

        $site = Site::factory()->active()->withRuntimeContext(['de-DE', 'en-DE'])->create([
            'domain' => 'public-chrome.test',
        ]);
        $site->load('locales.locale');

        $options = app(PublicLocaleNavigation::class)->forHome($site, 'de-DE');
        $html = Blade::render(
            '<x-public.locale-selector :options="$options" />',
            compact('options'),
        );

        $this->assertCount(2, $options);
        $this->assertStringContainsString('href="https://public-chrome.test/de-DE"', $html);
        $this->assertStringContainsString('href="https://public-chrome.test/en-DE"', $html);

        foreach ([
            'public-multi-category' => [PublicThemeId::MultiCategory, PublicLayoutType::MultiCategory],
            'public-single-category' => [PublicThemeId::SingleCategory, PublicLayoutType::SingleCategory],
        ] as $layout => [$identifier, $layoutType]) {
            $theme = new PublicThemeContext($identifier, $layoutType, [], []);
            $rendered = view("layouts.{$layout}", [
                'site' => $site,
                'locale' => 'de-DE',
                'theme' => $theme,
                'publicLocaleOptions' => $options,
            ])->render();
            $this->assertStringContainsString('data-public-header', $rendered);
            $this->assertStringContainsString('data-public-footer', $rendered);
        }
    }

    public function test_locale_navigation_rejects_partially_loaded_locale_relations(): void
    {
        $site = Site::factory()->active()->withRuntimeContext(['de-DE', 'en-DE'])->create();
        $site->unsetRelation('locales');
        $site->load('locales');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Site locale catalog relations must be loaded');

        app(PublicLocaleNavigation::class)->forHome($site, 'de-DE');
    }
}
