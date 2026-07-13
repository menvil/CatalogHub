<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpsertSiteOverrideAction;
use App\Enums\UserRole;
use App\Filament\Resources\SiteResource\Pages\LocalSeoOverride;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Concerns\EnablesSiteProductCategories;
use Tests\TestCase;

class LocalSeoOverrideTest extends TestCase
{
    use EnablesSiteProductCategories;
    use RefreshDatabase;

    public function test_locale_specific_meta_fields_are_saved_without_central_mutation(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $this->enableLocale($site, 'de-DE');
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
        $this->enableProductCategory($site, $product);
        $this->enableLocale($site, 'de-DE');
        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $product->id, 'meta_title', 'de-DE', 'Title');
        $action->handle($site, 'product', $product->id, 'meta_title', 'de-DE', '');

        $this->assertDatabaseMissing('site_overrides', ['site_id' => $site->id, 'field' => 'meta_title', 'locale_code' => 'de-DE']);
    }

    public function test_seo_controls_have_accessible_labels_and_required_locale(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(LocalSeoOverride::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('for="seo-entity-type"', false)
            ->assertSee('for="seo-entity-id"', false)
            ->assertSee('for="seo-locale"', false)
            ->assertSee('id="seo-locale"', false)
            ->assertSee('aria-required="true"', false)
            ->assertSee('for="seo-meta-title"', false)
            ->assertSee('for="seo-meta-description"', false)
            ->assertSee('for="seo-intro-text"', false);
    }

    public function test_catalog_editor_cannot_access_local_seo_editor(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(LocalSeoOverride::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    public function test_seo_validation_errors_are_visible_next_to_controls(): void
    {
        $site = Site::factory()->create();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(LocalSeoOverride::class, ['record' => $site->getRouteKey()])
            ->call('save')
            ->assertHasErrors(['entityId', 'localeCode'])
            ->assertSee('The entity id field is required.')
            ->assertSee('The locale code field is required.');
    }

    public function test_action_validation_errors_are_mapped_to_seo_editor_fields(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $admin = User::factory()->centralAdmin()->create();

        Livewire::actingAs($admin)
            ->test(LocalSeoOverride::class, ['record' => $site->getRouteKey()])
            ->set('entityId', PHP_INT_MAX)
            ->set('localeCode', 'de-DE')
            ->set('metaTitle', 'Ghost title')
            ->call('save')
            ->assertHasErrors(['entityId'])
            ->assertSee('The selected override target does not exist.');

        Livewire::actingAs($admin)
            ->test(LocalSeoOverride::class, ['record' => $site->getRouteKey()])
            ->set('entityId', $product->id)
            ->set('localeCode', 'de-DE')
            ->set('metaTitle', 'Unconfigured locale')
            ->call('save')
            ->assertHasErrors(['localeCode'])
            ->assertSee('The selected locale must be enabled for the site.');
    }

    public function test_editing_one_seo_field_preserves_the_other_existing_fields(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $this->enableProductCategory($site, $product);
        $this->enableLocale($site, 'de-DE');
        $action = app(UpsertSiteOverrideAction::class);
        $action->handle($site, 'product', $product->id, 'meta_title', 'de-DE', 'Old title');
        $action->handle($site, 'product', $product->id, 'meta_description', 'de-DE', 'Existing description');
        $action->handle($site, 'product', $product->id, 'intro_text', 'de-DE', 'Existing intro');

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(LocalSeoOverride::class, ['record' => $site->getRouteKey()])
            ->set('entityType', 'product')
            ->set('entityId', $product->id)
            ->set('localeCode', 'de-DE')
            ->call('loadExistingValues')
            ->assertSet('metaTitle', 'Old title')
            ->assertSet('metaDescription', 'Existing description')
            ->assertSet('introText', 'Existing intro')
            ->set('metaTitle', 'New title')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('site_overrides', ['site_id' => $site->id, 'field' => 'meta_description', 'value_json' => json_encode(['value' => 'Existing description'])]);
        $this->assertDatabaseHas('site_overrides', ['site_id' => $site->id, 'field' => 'intro_text', 'value_json' => json_encode(['value' => 'Existing intro'])]);
        $this->assertDatabaseHas('site_overrides', ['site_id' => $site->id, 'field' => 'meta_title', 'value_json' => json_encode(['value' => 'New title'])]);
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
