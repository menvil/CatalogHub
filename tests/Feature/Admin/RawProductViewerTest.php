<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\RawProductResource;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawProductViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_shows_raw_payload_source_status_errors_and_draft_link(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $rawProduct = RawProduct::factory()->create([
            'raw_title' => 'Legacy Kettle',
            'raw_brand' => 'Acme',
            'raw_category' => 'Kettles',
            'raw_payload_json' => [
                'title' => 'Legacy Kettle',
                'specifications' => ['Power' => '2200 W'],
            ],
            'status' => 'normalized',
            'error_message' => 'Legacy row could not be parsed completely',
        ]);
        $draft = NormalizedProductDraft::factory()
            ->for($rawProduct, 'rawProduct')
            ->create(['import_batch_id' => $rawProduct->import_batch_id]);
        NormalizationError::query()->create([
            'import_batch_id' => $rawProduct->import_batch_id,
            'raw_product_id' => $rawProduct->id,
            'normalized_product_draft_id' => $draft->id,
            'severity' => 'warning',
            'code' => 'unknown_attribute',
            'message' => 'Power key needs mapping',
        ]);

        $this->actingAs($admin)
            ->get(RawProductResource::getUrl('view', ['record' => $rawProduct]))
            ->assertOk()
            ->assertSee('Legacy Kettle')
            ->assertSee('Acme')
            ->assertSee('Kettles')
            ->assertSee('Power')
            ->assertSee('2200 W')
            ->assertSee($rawProduct->payload_hash)
            ->assertSee('unknown_attribute')
            ->assertSee('Legacy row could not be parsed completely')
            ->assertSee('Normalized draft');
    }
}
