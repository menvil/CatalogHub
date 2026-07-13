<?php

namespace Tests\Feature\Imports;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\RawProduct;
use App\Services\Imports\RawPayloadHasher;
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

    public function test_hash_is_stable_for_nested_json_objects_with_different_property_order(): void
    {
        $firstSpecs = new \stdClass;
        $firstSpecs->power = 100;
        $firstSpecs->dimensions = (object) ['width' => 20, 'height' => 30];

        $secondDimensions = new \stdClass;
        $secondDimensions->height = 30;
        $secondDimensions->width = 20;
        $secondSpecs = new \stdClass;
        $secondSpecs->dimensions = $secondDimensions;
        $secondSpecs->power = 100;

        $hasher = new RawPayloadHasher;

        $this->assertSame(
            $hasher->hash(['specs' => $firstSpecs]),
            $hasher->hash(['specs' => $secondSpecs]),
        );
    }

    public function test_factory_and_writer_use_the_same_payload_hash_algorithm(): void
    {
        $rawProduct = RawProduct::factory()->create();

        $this->assertSame(
            (new RawPayloadHasher)->hash($rawProduct->raw_payload_json),
            $rawProduct->payload_hash,
        );
    }

    public function test_limits_display_strings_and_does_not_truncate_external_identifiers(): void
    {
        $batch = ImportBatch::factory()->create();
        $title = str_repeat('Ж', 300);

        $rawProduct = (new RawProductWriter)->write($batch, [
            'id' => str_repeat('x', 300),
            'title' => $title,
            'brand' => str_repeat('b', 300),
            'category' => str_repeat('c', 300),
        ]);

        $this->assertNull($rawProduct->external_id);
        $this->assertSame(255, mb_strlen((string) $rawProduct->raw_title));
        $this->assertSame(255, mb_strlen((string) $rawProduct->raw_brand));
        $this->assertSame(255, mb_strlen((string) $rawProduct->raw_category));
        $this->assertSame(str_repeat('x', 300), $rawProduct->raw_payload_json['id']);
        $this->assertSame($title, $rawProduct->raw_payload_json['title']);
        $this->assertDatabaseHas('normalization_errors', [
            'import_batch_id' => $batch->id,
            'raw_product_id' => $rawProduct->id,
            'severity' => 'warning',
            'code' => 'external_id_too_long',
            'raw_key' => 'id',
            'raw_value' => str_repeat('x', 300),
        ]);
    }

    public function test_uses_a_later_identifier_when_an_earlier_candidate_is_too_long(): void
    {
        $batch = ImportBatch::factory()->create();

        $rawProduct = (new RawProductWriter)->write($batch, [
            'external_id' => str_repeat('x', 300),
            'id' => 'usable-id',
            'sku' => 'unused-sku',
        ]);

        $this->assertSame('usable-id', $rawProduct->external_id);
        $this->assertDatabaseMissing('normalization_errors', [
            'raw_product_id' => $rawProduct->id,
            'code' => 'external_id_too_long',
        ]);
    }

    public function test_uses_the_configured_serialized_payload_depth_limit(): void
    {
        config()->set('imports.serialized_php_max_depth', 3);
        $batch = ImportBatch::factory()->create();

        $this->expectException(JsonException::class);

        (new RawProductWriter)->write($batch, [
            'specs' => ['nested' => ['too' => ['deep' => true]]],
        ]);
    }

    public function test_accepts_payloads_deeper_than_the_old_hardcoded_limit_when_configured(): void
    {
        config()->set('imports.serialized_php_max_depth', 80);
        $batch = ImportBatch::factory()->create();
        $payload = ['leaf' => true];

        for ($depth = 0; $depth < 65; $depth++) {
            $payload = ['nested' => $payload];
        }

        $rawProduct = (new RawProductWriter)->write($batch, $payload);

        $this->assertSame(1, $batch->fresh()->raw_items_count);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $rawProduct->payload_hash);
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
