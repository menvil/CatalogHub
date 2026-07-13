<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\CreateSiteWizard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
