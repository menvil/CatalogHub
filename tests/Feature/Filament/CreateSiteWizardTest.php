<?php

namespace Tests\Feature\Filament;

use App\Enums\CentralCategoryStatus;
use App\Enums\MarketStatus;
use App\Filament\Pages\CreateSiteWizard;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Market;
use App\Models\SiteFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateSiteWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_render_create_site_wizard(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(CreateSiteWizard::getUrl())
            ->assertOk()
            ->assertSee('Create Site Wizard')
            ->assertSee('Review & Create');
    }

    public function test_default_locale_options_are_limited_to_enabled_locales(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        Locale::factory()->create(['code' => 'de-DE', 'name' => 'German']);
        Locale::factory()->create(['code' => 'en-US', 'name' => 'English']);

        Livewire::actingAs($admin)
            ->test(CreateSiteWizard::class)
            ->set('enabledLocales', ['de-DE'])
            ->assertSeeHtml('<option value="de-DE">German</option>')
            ->assertDontSeeHtml('<option value="en-US">English</option>');
    }

    public function test_step_progression_is_bounded(): void
    {
        $component = Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(CreateSiteWizard::class)
            ->assertSet('currentStep', 0)
            ->call('previousStep')
            ->assertSet('currentStep', 0)
            ->call('nextStep')
            ->assertSet('currentStep', 1);

        for ($step = 0; $step < 10; $step++) {
            $component->call('nextStep');
        }

        $component->assertSet('currentStep', 6);
    }

    public function test_feature_options_use_the_canonical_feature_keys(): void
    {
        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(CreateSiteWizard::class)
            ->assertSet('features', array_fill_keys(SiteFeature::KEYS, false));
    }

    public function test_locale_checkboxes_update_default_options_live(): void
    {
        Locale::factory()->create(['code' => 'de-DE', 'name' => 'German']);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(CreateSiteWizard::class)
            ->assertSeeHtml('wire:model.live="enabledLocales"')
            ->set('enabledLocales', ['de-DE'])
            ->assertSeeHtml('<option value="de-DE">German</option>');
    }

    public function test_validation_errors_are_rendered_next_to_wizard_fields(): void
    {
        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(CreateSiteWizard::class)
            ->call('createSite')
            ->assertHasErrors(['code', 'name', 'marketId', 'enabledLocales', 'defaultLocale', 'enabledCategories'])
            ->assertSee('The code field is required.')
            ->assertSee('The default locale field is required.');
    }

    public function test_inputs_are_labelled_and_dynamic_options_have_stable_keys(): void
    {
        $locale = Locale::factory()->create();
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(CreateSiteWizard::getUrl())
            ->assertOk()
            ->assertSee('for="site-code"', false)
            ->assertSee('for="site-name"', false)
            ->assertSee('for="site-domain"', false)
            ->assertSee('wire:key="site-locale-'.$locale->id.'"', false)
            ->assertSee('wire:key="site-category-'.$category->id.'"', false)
            ->assertSee('wire:key="site-feature-comparison"', false);
    }

    public function test_action_validation_errors_are_mapped_to_wizard_field_names(): void
    {
        $market = Market::factory()->create(['status' => MarketStatus::Archived]);
        $locale = Locale::factory()->create(['code' => 'en-US']);
        $category = CentralCategory::factory()->create(['status' => CentralCategoryStatus::Active]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(CreateSiteWizard::class)
            ->set('code', 'inactive-market')
            ->set('name', 'Inactive market')
            ->set('marketId', $market->id)
            ->set('mode', 'single_category')
            ->set('enabledLocales', [$locale->code])
            ->set('defaultLocale', $locale->code)
            ->set('enabledCategories', [$category->id])
            ->call('createSite')
            ->assertHasErrors(['marketId'])
            ->assertHasNoErrors(['market_id'])
            ->assertSee('The selected market must be active.');
    }
}
