<?php

namespace Tests\Feature\Imports;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Services\Imports\RawProductWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use JsonException;
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

    public function test_limits_extracted_strings_without_losing_the_full_payload(): void
    {
        $batch = ImportBatch::factory()->create();
        $title = str_repeat('Ж', 300);

        $rawProduct = (new RawProductWriter)->write($batch, [
            'id' => str_repeat('x', 300),
            'title' => $title,
            'brand' => str_repeat('b', 300),
            'category' => str_repeat('c', 300),
        ]);

        $this->assertSame(255, mb_strlen((string) $rawProduct->external_id));
        $this->assertSame(255, mb_strlen((string) $rawProduct->raw_title));
        $this->assertSame($title, $rawProduct->raw_payload_json['title']);
    }

    public function test_rejects_recursive_payload_before_canonicalization(): void
    {
        $batch = ImportBatch::factory()->create();
        $payload = [];
        $payload['self'] = &$payload;

        $this->expectException(JsonException::class);

        (new RawProductWriter)->write($batch, $payload);
    }
}
