<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Site;
use App\Services\Sites\SiteOverrideResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LocalTitleOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_prefers_active_local_title_without_mutating_central_title(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['name' => 'Canonical Title']);
        $this->enableLocale($site, 'de-DE');
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

    public function test_resolver_uses_empty_locale_sentinel_for_global_override(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableLocale($site, 'de-DE');
        $action = app(UpsertSiteOverrideAction::class);
        $resolver = app(SiteOverrideResolver::class);
        $action->handle($site, 'product', $product->id, 'local_title', null, 'Global title');

        $this->assertDatabaseHas('site_overrides', [
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => 'local_title',
            'locale_code' => '',
        ]);
        $this->assertSame('Global title', $resolver->resolve($site, 'product', $product->id, 'local_title', null, 'Translated', 'Fallback'));
        $this->assertSame('Global title', $resolver->resolve($site, 'product', $product->id, 'local_title', 'de-DE', 'Translated', 'Fallback'));

        $action->handle($site, 'product', $product->id, 'local_title', 'de-DE', 'German title');
        $this->assertSame('German title', $resolver->resolve($site, 'product', $product->id, 'local_title', 'de-DE', 'Translated', 'Fallback'));
    }

    private function enableLocale(Site $site, string $code): void
    {
        Locale::factory()->create(['code' => $code]);
        DB::table('site_locales')->insert([
            'site_id' => $site->id,
            'locale_code' => $code,
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
