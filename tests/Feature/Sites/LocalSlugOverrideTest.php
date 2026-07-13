<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LocalSlugOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_slug_is_saved_without_changing_canonical_slug(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['slug' => 'canonical-slug']);
        app(UpsertSiteOverrideAction::class)->handle($site, 'product', $product->id, 'local_slug', 'de-DE', 'lokaler-slug');

        $this->assertDatabaseHas('site_overrides', ['site_id' => $site->id, 'field' => 'local_slug', 'locale_code' => 'de-DE']);
        $this->assertSame('canonical-slug', $product->fresh()->slug);
    }

    public function test_duplicate_or_invalid_local_slug_is_blocked(): void
    {
        $site = Site::factory()->create();
        $products = CentralProduct::factory()->count(2)->create();
        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $products[0]->id, 'local_slug', 'de-DE', 'same-slug');

        $this->expectException(ValidationException::class);
        $action->handle($site, 'product', $products[1]->id, 'local_slug', 'de-DE', 'same-slug');
    }

    public function test_slug_with_spaces_or_uppercase_is_blocked(): void
    {
        $this->expectException(ValidationException::class);
        app(UpsertSiteOverrideAction::class)->handle(Site::factory()->create(), 'product', CentralProduct::factory()->create()->id, 'local_slug', 'de-DE', 'Invalid Slug');
    }
}
