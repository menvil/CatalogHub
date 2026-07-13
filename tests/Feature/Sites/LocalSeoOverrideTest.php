<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalSeoOverrideTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_specific_meta_fields_are_saved_without_central_mutation(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $name = $product->name;
        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $product->id, 'meta_title', 'de-DE', 'Deutscher Metatitel');
        $action->handle($site, 'product', $product->id, 'meta_description', 'de-DE', 'Deutsche Beschreibung');

        $this->assertDatabaseHas('site_overrides', ['site_id' => $site->id, 'field' => 'meta_title', 'locale_code' => 'de-DE']);
        $this->assertDatabaseHas('site_overrides', ['site_id' => $site->id, 'field' => 'meta_description', 'locale_code' => 'de-DE']);
        $this->assertSame($name, $product->fresh()->name);
    }

    public function test_empty_seo_value_removes_override(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $product->id, 'meta_title', 'de-DE', 'Title');
        $action->handle($site, 'product', $product->id, 'meta_title', 'de-DE', '');

        $this->assertDatabaseMissing('site_overrides', ['site_id' => $site->id, 'field' => 'meta_title', 'locale_code' => 'de-DE']);
    }
}
