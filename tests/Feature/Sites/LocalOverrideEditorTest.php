<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Filament\Resources\SiteResource\Pages\LocalOverrideEditor;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
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

    public function test_editor_can_clear_an_existing_override(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        app(UpsertSiteOverrideAction::class)->handle($site, 'product', $product->id, 'local_title', 'de-DE', 'Local title');

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(LocalOverrideEditor::class, ['record' => $site->getRouteKey()])
            ->set('entityType', 'product')
            ->set('entityId', $product->id)
            ->set('field', 'local_title')
            ->set('localeCode', 'de-DE')
            ->set('value', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('site_overrides', [
            'site_id' => $site->id,
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'field' => 'local_title',
            'locale_code' => 'de-DE',
        ]);
    }

    public function test_editor_controls_have_accessible_labels(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(LocalOverrideEditor::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('for="override-entity-type"', false)
            ->assertSee('for="override-entity-id"', false)
            ->assertSee('for="override-field"', false)
            ->assertSee('for="override-locale"', false)
            ->assertSee('for="override-value"', false)
            ->assertSee('for="override-reason"', false);
    }
}
