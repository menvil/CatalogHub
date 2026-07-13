<?php

namespace Tests\Feature\Imports;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\DuplicateCandidate;
use App\Models\Imports\NormalizedProductDraft;
use App\Services\Imports\DuplicateDetector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateDetectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_high_score_for_exact_title_brand_and_category_match(): void
    {
        $brand = CentralBrand::factory()->create();
        $category = CentralCategory::factory()->create();
        $product = CentralProduct::factory()->for($brand, 'brand')->for($category, 'category')->create([
            'name' => 'Acme Mixer Pro',
            'model' => 'MX-500',
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'title' => 'Acme Mixer Pro',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);

        $candidates = (new DuplicateDetector)->detect($draft);

        $candidate = $candidates->sole();
        $this->assertSame('central_product', $candidate->candidate_type);
        $this->assertSame($product->id, $candidate->candidate_id);
        $this->assertGreaterThanOrEqual(0.9, (float) $candidate->score);
        $this->assertTrue($candidate->reason_json['brand_match']);
        $this->assertTrue($candidate->reason_json['category_match']);
        $this->assertNull($draft->fresh()->matched_central_product_id);
        $this->assertSame(1, CentralProduct::query()->count());
    }

    public function test_different_brand_lowers_candidate_score(): void
    {
        $matchingBrand = CentralBrand::factory()->create();
        $matchingCategory = CentralCategory::factory()->create();
        $otherBrand = CentralBrand::factory()->create();
        $exact = CentralProduct::factory()
            ->for($matchingBrand, 'brand')
            ->for($matchingCategory, 'category')
            ->create(['name' => 'Shared Product Name']);
        $different = CentralProduct::factory()
            ->for($otherBrand, 'brand')
            ->for($matchingCategory, 'category')
            ->create(['name' => 'Shared Product Name']);
        $draft = NormalizedProductDraft::factory()->create([
            'title' => 'Shared Product Name',
            'brand_id' => $matchingBrand->id,
            'category_id' => $matchingCategory->id,
        ]);

        (new DuplicateDetector)->detect($draft);

        $exactScore = (float) DuplicateCandidate::query()->where('candidate_id', $exact->id)->sole()->score;
        $differentScore = (float) DuplicateCandidate::query()->where('candidate_id', $different->id)->sole()->score;
        $this->assertGreaterThan($differentScore, $exactScore);
    }

    public function test_exact_title_match_is_not_excluded_by_different_brand_and_category(): void
    {
        $product = CentralProduct::factory()->create(['name' => 'Shared Product Name']);
        $draft = NormalizedProductDraft::factory()->create([
            'title' => 'Shared Product Name',
            'brand_id' => CentralBrand::factory()->create()->id,
            'category_id' => CentralCategory::factory()->create()->id,
        ]);

        $candidate = (new DuplicateDetector)->detect($draft)->sole();

        $this->assertSame($product->id, $candidate->candidate_id);
        $this->assertSame('0.5500', $candidate->score);
        $this->assertFalse($candidate->reason_json['brand_match']);
        $this->assertFalse($candidate->reason_json['category_match']);
    }

    public function test_unrelated_product_does_not_create_candidate(): void
    {
        CentralProduct::factory()->create(['name' => 'Completely Different Refrigerator']);
        $draft = NormalizedProductDraft::factory()->create(['title' => 'Compact Gaming Mouse']);

        $candidates = (new DuplicateDetector)->detect($draft);

        $this->assertCount(0, $candidates);
        $this->assertSame(0, DuplicateCandidate::query()->count());
    }

    public function test_redetection_preserves_an_existing_review_decision(): void
    {
        $brand = CentralBrand::factory()->create();
        $category = CentralCategory::factory()->create();
        $product = CentralProduct::factory()->for($brand, 'brand')->for($category, 'category')->create([
            'name' => 'Reviewed Mixer',
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'title' => 'Reviewed Mixer',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        DuplicateCandidate::query()->create([
            'import_batch_id' => $draft->import_batch_id,
            'normalized_product_draft_id' => $draft->id,
            'candidate_type' => 'central_product',
            'candidate_id' => $product->id,
            'score' => '0.9000',
            'reason_json' => [],
            'status' => 'confirmed_duplicate',
        ]);

        (new DuplicateDetector)->detect($draft);

        $this->assertSame('confirmed_duplicate', DuplicateCandidate::query()->sole()->status);
    }

    public function test_redetection_removes_pending_candidates_that_no_longer_match(): void
    {
        $brand = CentralBrand::factory()->create();
        $category = CentralCategory::factory()->create();
        $product = CentralProduct::factory()->for($brand, 'brand')->for($category, 'category')->create([
            'name' => 'Acme Mixer Pro',
        ]);
        $draft = NormalizedProductDraft::factory()->create([
            'title' => 'Acme Mixer Pro',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        $detector = new DuplicateDetector;
        $detector->detect($draft);
        $this->assertSame(1, DuplicateCandidate::query()->count());

        $product->update(['name' => 'ZZZZZZZZZZZZZZZZ']);
        $candidates = $detector->detect($draft);

        $this->assertCount(0, $candidates);
        $this->assertSame(0, DuplicateCandidate::query()->count());
    }
}
