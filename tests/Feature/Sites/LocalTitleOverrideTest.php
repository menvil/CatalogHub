<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Services\Sites\SiteOverrideResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalTitleOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_prefers_active_local_title_without_mutating_central_title(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['name' => 'Canonical Title']);
        app(UpsertSiteOverrideAction::class)->handle($site, 'product', $product->id, 'local_title', 'de-DE', 'Lokaler Titel');

        $resolved = app(SiteOverrideResolver::class)->resolve($site, 'product', $product->id, 'local_title', 'de-DE', 'Übersetzter Titel', $product->name);

        $this->assertSame('Lokaler Titel', $resolved);
        $this->assertSame('Canonical Title', $product->fresh()->name);
    }

    public function test_resolver_falls_back_to_translation_then_fallback_value(): void
    {
        $resolver = app(SiteOverrideResolver::class);
        $site = Site::factory()->create();

        $this->assertSame('Translated', $resolver->resolve($site, 'product', 42, 'local_title', 'de-DE', 'Translated', 'Fallback'));
        $this->assertSame('Fallback', $resolver->resolve($site, 'product', 42, 'local_title', 'de-DE', null, 'Fallback'));
    }
}
