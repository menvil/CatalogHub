<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\NormalizationErrorResource;
use App\Filament\Resources\NormalizationErrorResource\Pages\ListNormalizationErrors;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NormalizationErrorsScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_by_batch_severity_and_code_and_shows_context_links(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $draft = NormalizedProductDraft::factory()->create();
        $visible = NormalizationError::query()->create([
            'import_batch_id' => $draft->import_batch_id,
            'raw_product_id' => $draft->raw_product_id,
            'normalized_product_draft_id' => $draft->id,
            'severity' => 'warning',
            'code' => 'unknown_enum_option',
            'message' => 'Purple is unknown',
            'raw_key' => 'Color',
            'raw_value' => 'Purple',
            'context_json' => ['available' => ['black', 'white']],
        ]);
        $otherBatch = ImportBatch::factory()->create();
        $hidden = NormalizationError::query()->create([
            'import_batch_id' => $otherBatch->id,
            'severity' => 'critical',
            'code' => 'invalid_payload',
            'message' => 'Broken',
        ]);

        Livewire::actingAs($admin)
            ->test(ListNormalizationErrors::class)
            ->filterTable('batch', $visible->import_batch_id)
            ->filterTable('severity', 'warning')
            ->filterTable('code', 'unknown_enum_option')
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);

        $this->actingAs($admin)
            ->get(NormalizationErrorResource::getUrl('view', ['record' => $visible]))
            ->assertOk()
            ->assertSee('Purple is unknown')
            ->assertSee('Color')
            ->assertSee('Purple')
            ->assertSee('available')
            ->assertSee('Raw product')
            ->assertSee('Normalized draft')
            ->assertSee('Import batch');
    }

    public function test_can_mark_error_resolved(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $error = NormalizationError::query()->create([
            'import_batch_id' => ImportBatch::factory()->create()->id,
            'severity' => 'error',
            'code' => 'missing_mapping',
            'message' => 'Mapping missing',
        ]);

        Livewire::actingAs($admin)
            ->test(ListNormalizationErrors::class)
            ->callTableAction('resolve', $error);

        $error = $error->fresh();
        $this->assertNotNull($error->resolved_at);
        $this->assertSame($admin->id, $error->resolved_by_user_id);
    }
}
