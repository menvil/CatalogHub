<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\ImportBatchResource;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\ImportArtifact;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportBatchDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_detail_shows_source_status_counters_error_and_related_links(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $source = ImportSource::factory()->create(['name' => 'Legacy source']);
        $batch = ImportBatch::factory()->for($source, 'source')->create([
            'status' => 'failed',
            'total_items' => 10,
            'raw_items_count' => 8,
            'drafts_count' => 4,
            'failed_count' => 2,
            'error_message' => 'Two rows could not be parsed',
        ]);
        ImportArtifact::query()->create([
            'import_batch_id' => $batch->id,
            'type' => 'original',
            'disk' => 'local',
            'path' => 'imports/test.data',
            'checksum' => hash('sha256', 'test'),
            'metadata_json' => [],
        ]);
        $draft = NormalizedProductDraft::factory()->for($batch, 'importBatch')->create();
        NormalizationError::query()->create([
            'import_batch_id' => $batch->id,
            'severity' => 'error',
            'code' => 'invalid_row',
            'message' => 'Invalid row',
        ]);
        DuplicateCandidate::query()->create([
            'import_batch_id' => $batch->id,
            'normalized_product_draft_id' => $draft->id,
            'candidate_type' => 'central_product',
            'candidate_id' => 999,
            'score' => '0.9000',
            'reason_json' => ['title_similarity' => 1],
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->get(ImportBatchResource::getUrl('view', ['record' => $batch]))
            ->assertOk()
            ->assertSee('Legacy source')
            ->assertSee('failed')
            ->assertSee('Two rows could not be parsed')
            ->assertSee('Raw products')
            ->assertSee('Drafts')
            ->assertSee('Errors')
            ->assertSee('Duplicates');
    }
}
