<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Pages\TranslationDashboard;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_view_translation_dashboard(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        Locale::factory()->create(['code' => 'de-DE', 'is_active' => true]);

        $this->actingAs($admin)
            ->get(route('central.translations.dashboard'))
            ->assertOk()
            ->assertSee('Translation Dashboard')
            ->assertSee('Translation Coverage');
    }

    public function test_blocks_user_without_translation_permission_from_translation_dashboard(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        $this->actingAs($moderator)
            ->get(route('central.translations.dashboard'))
            ->assertForbidden();
    }

    public function test_filament_translation_dashboard_requires_translation_permission(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($moderator);
        $this->assertFalse(TranslationDashboard::canAccess());

        $this->actingAs($admin);
        $this->assertTrue(TranslationDashboard::canAccess());
    }
}
