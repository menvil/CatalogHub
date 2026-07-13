<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\CreateSiteWizard;
use App\Models\Locale;
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
}
