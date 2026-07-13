<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LocalOverrideEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allowed_presentation_override_is_upserted(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $product->id, 'local_title', 'de-DE', 'Lokaler Titel');
        $override = $action->handle($site, 'product', $product->id, 'local_title', 'de-DE', 'Neuer Titel');

        $this->assertDatabaseCount('site_overrides', 1);
        $this->assertSame(['value' => 'Neuer Titel'], $override->value_json);
    }

    public function test_canonical_field_override_is_blocked(): void
    {
        $this->expectException(ValidationException::class);
        app(UpsertSiteOverrideAction::class)->handle(Site::factory()->create(), 'product', CentralProduct::factory()->create()->id, 'brand_id', null, 123);
    }
}
