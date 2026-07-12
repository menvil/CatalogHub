<?php

namespace Tests\Feature\Services;

use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Services\Media\MediaResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_global_assignment_for_entity_and_role(): void
    {
        $asset = MediaAsset::factory()->create();
        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
            'locale' => null,
        ]);

        $resolved = app(MediaResolver::class)->resolve('central_product', 123, 'main');

        $this->assertTrue($resolved?->is($asset));
    }

    public function test_prefers_localized_assignment_over_global_assignment(): void
    {
        $globalAsset = MediaAsset::factory()->create();
        $localizedAsset = MediaAsset::factory()->create();

        MediaAssignment::factory()->for($globalAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'hero',
            'locale' => null,
        ]);
        MediaAssignment::factory()->for($localizedAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'hero',
            'locale' => 'de-DE',
        ]);

        $resolved = app(MediaResolver::class)->resolve('central_product', 123, 'hero', locale: 'de-DE');

        $this->assertSame($localizedAsset->id, $resolved?->id);
    }

    public function test_prefers_site_specific_assignment_over_global_assignment(): void
    {
        $globalAsset = MediaAsset::factory()->create();
        $siteAsset = MediaAsset::factory()->create();

        MediaAssignment::factory()->for($globalAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
            'site_id' => null,
        ]);
        MediaAssignment::factory()->for($siteAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
            'site_id' => 10,
        ]);

        $resolved = app(MediaResolver::class)->resolve('central_product', 123, 'main', siteId: 10);

        $this->assertSame($siteAsset->id, $resolved?->id);
    }

    public function test_prefers_market_specific_assignment_over_global_assignment(): void
    {
        $globalAsset = MediaAsset::factory()->create();
        $marketAsset = MediaAsset::factory()->create();

        MediaAssignment::factory()->for($globalAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'hero',
            'market_id' => null,
        ]);
        MediaAssignment::factory()->for($marketAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'hero',
            'market_id' => 5,
        ]);

        $resolved = app(MediaResolver::class)->resolve('central_product', 123, 'hero', marketId: 5);

        $this->assertSame($marketAsset->id, $resolved?->id);
    }

    public function test_resolves_site_and_market_assignment_without_locale(): void
    {
        $globalAsset = MediaAsset::factory()->create();
        $scopedAsset = MediaAsset::factory()->create();

        MediaAssignment::factory()->for($globalAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
            'site_id' => null,
            'market_id' => null,
        ]);
        MediaAssignment::factory()->for($scopedAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
            'site_id' => 10,
            'market_id' => 5,
            'locale' => null,
        ]);

        $resolved = app(MediaResolver::class)->resolve('central_product', 123, 'main', siteId: 10, marketId: 5);

        $this->assertSame($scopedAsset->id, $resolved?->id);
    }

    public function test_role_fallback_and_placeholder_explanation_are_deterministic(): void
    {
        $asset = MediaAsset::factory()->create();
        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => 123,
            'role' => 'main',
        ]);

        $resolver = app(MediaResolver::class);

        $this->assertSame($asset->id, $resolver->resolve('central_product', 123, 'card')?->id);

        $result = $resolver->explain('central_product', 999, 'og');

        $this->assertFalse($result->found());
        $this->assertSame('placeholder', $result->matchedStep);
        $this->assertContains('placeholder', $result->fallbackChain);
    }
}
