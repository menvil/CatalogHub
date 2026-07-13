<?php

namespace Tests\Feature\Imports;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Services\Imports\RawProductWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawPayloadPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_preserves_all_fields_and_extracts_common_source_values(): void
    {
        $source = ImportSource::factory()->create();
        $batch = ImportBatch::factory()->for($source, 'source')->create();
        $payload = [
            'id' => 'source-42',
            'name' => 'Imported kettle',
            'manufacturer' => 'Acme',
            'category_name' => 'Kettles',
            'unknown' => ['nested' => ['still' => 'present']],
        ];

        $rawProduct = (new RawProductWriter)->write($batch, $payload, 17);

        $this->assertSame($payload, $rawProduct->fresh()->raw_payload_json);
        $this->assertSame('source-42', $rawProduct->external_id);
        $this->assertSame('Imported kettle', $rawProduct->raw_title);
        $this->assertSame('Acme', $rawProduct->raw_brand);
        $this->assertSame('Kettles', $rawProduct->raw_category);
        $this->assertSame(17, $rawProduct->source_row_number);
        $this->assertSame(1, $batch->fresh()->raw_items_count);
    }

    public function test_hash_is_stable_for_equivalent_associative_key_order(): void
    {
        $source = ImportSource::factory()->create();
        $batch = ImportBatch::factory()->for($source, 'source')->create();
        $writer = new RawProductWriter;

        $first = $writer->write($batch, [
            'title' => 'Same product',
            'specs' => ['power' => 100, 'voltage' => 230],
        ], 1);
        $second = $writer->write($batch, [
            'specs' => ['voltage' => 230, 'power' => 100],
            'title' => 'Same product',
        ], 2);

        $this->assertSame($first->payload_hash, $second->payload_hash);
        $this->assertSame(2, $batch->fresh()->raw_items_count);
    }
}
