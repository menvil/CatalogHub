<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Enums\UserRole;
use App\Filament\Resources\SiteResource\Pages\LocalOverrideEditor;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\Concerns\EnablesSiteProductCategories;
use Tests\TestCase;

class LocalOverrideEditorTest extends TestCase
{
    use EnablesSiteProductCategories;
    use RefreshDatabase;

    public function test_allowed_presentation_override_is_upserted(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $this->enableLocale($site, 'de-DE');
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
        $this->enableProductCategory($site, $product);
        $this->enableLocale($site, 'de-DE');
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

    public function test_catalog_editor_cannot_access_local_override_editor(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(LocalOverrideEditor::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    public function test_editor_renders_validation_errors_and_multiline_values(): void
    {
        $site = Site::factory()->create();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(LocalOverrideEditor::class, ['record' => $site->getRouteKey()])
            ->call('save')
            ->assertHasErrors(['entityId'])
            ->assertSee('The entity id field is required.')
            ->set('field', 'intro_text')
            ->assertSeeHtml('<textarea id="override-value"');
    }

    public function test_action_validation_errors_are_mapped_to_editor_fields(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $admin = User::factory()->centralAdmin()->create();

        Livewire::actingAs($admin)
            ->test(LocalOverrideEditor::class, ['record' => $site->getRouteKey()])
            ->set('entityId', PHP_INT_MAX)
            ->set('value', 'Ghost title')
            ->call('save')
            ->assertHasErrors(['entityId'])
            ->assertSee('The selected override target does not exist.');

        Livewire::actingAs($admin)
            ->test(LocalOverrideEditor::class, ['record' => $site->getRouteKey()])
            ->set('entityId', $product->id)
            ->set('localeCode', 'de-DE')
            ->set('value', 'Unconfigured locale')
            ->call('save')
            ->assertHasErrors(['localeCode'])
            ->assertSee('The selected locale must be enabled for the site.');

        Livewire::actingAs($admin)
            ->test(LocalOverrideEditor::class, ['record' => $site->getRouteKey()])
            ->set('entityId', $product->id)
            ->set('field', 'local_slug')
            ->set('value', 'Invalid Slug')
            ->call('save')
            ->assertHasErrors(['value']);
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
