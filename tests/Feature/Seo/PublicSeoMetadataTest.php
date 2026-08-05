<?php

declare(strict_types=1);

namespace Tests\Feature\Seo;

use App\Enums\SiteDomainType;
use App\Models\Locale;
use App\Models\Site;
use App\Services\Sites\SiteContextValueResolver;
use App\Support\Seo\SeoMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class PublicSeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_alias_request_canonicalizes_to_primary_domain_and_lists_only_enabled_locales(): void
    {
        $site = Site::factory()->active()->withRuntimeContext(['de-DE', 'en-DE', 'fr-FR'])->create([
            'domain' => 'primary-seo.test',
            'name' => 'Catalog <unsafe>',
            'settings_json' => [
                'seo' => [
                    'meta_title' => 'Configured <title>',
                    'meta_description' => 'Compare <trusted> products.',
                ],
            ],
        ]);
        $site->locales()->where('locale_code', 'fr-FR')->update(['is_enabled' => false]);
        $site->domains()->create([
            'host' => 'alias-seo.test',
            'type' => SiteDomainType::Alias,
            'is_primary' => false,
            'is_active' => true,
        ]);

        $response = $this->get('http://alias-seo.test/en-DE');

        $response->assertOk()
            ->assertSee('<title>Configured &lt;title&gt;</title>', false)
            ->assertSee('<meta name="description" content="Compare &lt;trusted&gt; products.">', false)
            ->assertSee('<link rel="canonical" href="https://primary-seo.test/en-DE">', false)
            ->assertSee('<link rel="alternate" hreflang="de-DE" href="https://primary-seo.test/de-DE">', false)
            ->assertSee('<link rel="alternate" hreflang="en-DE" href="https://primary-seo.test/en-DE">', false)
            ->assertDontSee('hreflang="fr-FR"', false)
            ->assertDontSee('<title>Configured <title></title>', false);
    }

    public function test_metadata_does_not_emit_duplicate_canonical_or_locale_links(): void
    {
        Site::factory()->active()->withRuntimeContext(['en-US'])->create([
            'domain' => 'one-locale-seo.test',
        ]);

        $html = $this->get('http://one-locale-seo.test/en-US')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'rel="canonical"'));
        $this->assertSame(1, substr_count($html, 'hreflang="en-US"'));
    }

    public function test_blank_description_is_not_rendered(): void
    {
        $html = Blade::render(
            '<x-public.seo-meta :metadata="$metadata" />',
            ['metadata' => new SeoMetadata('Title', '   ', 'https://seo.test/en-US', [])],
        );

        $this->assertStringNotContainsString('name="description"', $html);
    }

    public function test_route_incompatible_enabled_locale_fails_before_alternate_generation(): void
    {
        $site = Site::factory()->active()->withRuntimeContext(['en-US'])->create([
            'domain' => 'invalid-locale-seo.test',
        ]);
        Locale::factory()->create(['code' => 'english-US']);
        $site->locales()->create([
            'locale_code' => 'english-US',
            'is_default' => false,
            'is_enabled' => true,
            'position' => 1,
        ]);
        $site->load(['domains', 'market', 'locales.locale']);
        $domain = $site->domains->firstWhere('is_primary', true);
        self::assertNotNull($domain);
        $context = app(SiteContextValueResolver::class)->resolve($site, $domain, 'en-US');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Public SEO locale [english-US] cannot be used in localized routes.');

        SeoMetadata::forHome($context);
    }
}
