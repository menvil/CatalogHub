<?php

namespace Tests\Feature\CentralAdmin;

use App\Filament\Resources\ChangeRequestResource;
use App\Models\ChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CorrectionDiffViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_correction_detail_renders_reusable_diff_with_evidence(): void
    {
        $request = ChangeRequest::factory()->pending()->create([
            'field_path' => 'refresh_rate',
            'old_value_json' => ['refresh_rate' => 144],
            'proposed_value_json' => ['refresh_rate' => 165],
            'evidence_url' => 'https://manufacturer.example/specification',
            'evidence_note' => 'Manufacturer specification confirms 165 Hz.',
        ]);

        $response = $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(ChangeRequestResource::getUrl('view', ['record' => $request]));

        $response
            ->assertOk()
            ->assertSee('data-admin-diff-viewer="side-by-side"', escape: false)
            ->assertSee('refresh_rate')
            ->assertSee('144')
            ->assertSee('165')
            ->assertSee('manufacturer.example/specification')
            ->assertSee('Manufacturer specification confirms 165 Hz.');
    }

    public function test_version_history_preview_uses_the_same_diff_viewer(): void
    {
        $request = ChangeRequest::factory()->pending()->create();
        $version = $request->centralProduct->versions()->create([
            'version' => 2,
            'change_type' => 'correction',
            'diff_json' => ['name' => ['old' => 'Old name', 'new' => 'New name']],
        ]);

        $preview = view(
            'filament.resources.central-product-resource.pages.version-history-entry',
            ['version' => $version],
        )->render();

        $this->assertStringContainsString('data-admin-diff-viewer="side-by-side"', $preview);
        $this->assertStringContainsString('Old name', $preview);
        $this->assertStringContainsString('New name', $preview);
    }
}
