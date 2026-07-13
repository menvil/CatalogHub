<?php

namespace Tests\Feature\Factories;

use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportFactoryConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_product_factory_uses_its_batch_source(): void
    {
        $rawProduct = RawProduct::factory()->create();

        $this->assertSame($rawProduct->batch->import_source_id, $rawProduct->import_source_id);
    }

    public function test_normalized_draft_factory_uses_its_raw_product_batch(): void
    {
        $draft = NormalizedProductDraft::factory()->create();

        $this->assertSame($draft->rawProduct->import_batch_id, $draft->import_batch_id);
        $this->assertSame($draft->rawProduct->import_source_id, $draft->importBatch->import_source_id);
    }
}
