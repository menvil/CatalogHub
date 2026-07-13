<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\DuplicateCandidateResource;
use App\Filament\Resources\DuplicateCandidateResource\Pages\ListDuplicateCandidates;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DuplicateCandidatesScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_and_detail_show_draft_candidate_score_and_reasons(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        [$candidate, $product] = $this->candidate();

        $this->actingAs($admin)
            ->get(DuplicateCandidateResource::getUrl())
            ->assertOk()
            ->assertSee($candidate->draft->title)
            ->assertSee($product->name);

        $this->actingAs($admin)
            ->get(DuplicateCandidateResource::getUrl('view', ['record' => $candidate]))
            ->assertOk()
            ->assertSee('0.9100')
            ->assertSee('title_similarity')
            ->assertSee('brand_match');
    }

    public function test_review_actions_mark_duplicate_or_not_duplicate_without_merging(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        [$candidate] = $this->candidate();
        $productCount = CentralProduct::query()->count();

        Livewire::actingAs($admin)
            ->test(ListDuplicateCandidates::class)
            ->callTableAction('markDuplicate', $candidate);

        $candidate = $candidate->fresh();
        $this->assertSame('confirmed_duplicate', $candidate->status);
        $this->assertSame($admin->id, $candidate->reviewed_by_user_id);
        $this->assertNotNull($candidate->reviewed_at);
        $this->assertNull($candidate->draft->matched_central_product_id);

        Livewire::actingAs($admin)
            ->test(ListDuplicateCandidates::class)
            ->callTableAction('markNotDuplicate', $candidate);

        $this->assertSame('not_duplicate', $candidate->fresh()->status);
        $this->assertSame($productCount, CentralProduct::query()->count());
    }

    /** @return array{DuplicateCandidate, CentralProduct} */
    private function candidate(): array
    {
        $product = CentralProduct::factory()->create(['name' => 'Existing Mixer']);
        $draft = NormalizedProductDraft::factory()->create(['title' => 'Imported Mixer']);
        $candidate = DuplicateCandidate::query()->create([
            'import_batch_id' => $draft->import_batch_id,
            'normalized_product_draft_id' => $draft->id,
            'candidate_type' => 'central_product',
            'candidate_id' => $product->id,
            'score' => '0.9100',
            'reason_json' => ['title_similarity' => 0.95, 'brand_match' => true],
            'status' => 'pending',
        ]);

        return [$candidate, $product];
    }
}
