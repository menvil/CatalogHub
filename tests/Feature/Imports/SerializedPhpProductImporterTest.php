<?php

namespace Tests\Feature\Imports;

use App\Importers\SerializedPhpProductImporter;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\RawProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use Tests\TestCase;

class SerializedPhpProductImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_imports_serialized_fixture_into_raw_products_only(): void
    {
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
        ]);
        $batch = ImportBatch::factory()->for($source, 'source')->create();
        $fixture = base_path('tests/Fixtures/imports/serialized_products_sample.phpdata');

        (new SerializedPhpProductImporter)->import($batch, $fixture);

        $rawProducts = RawProduct::query()->orderBy('source_row_number')->get();

        $this->assertCount(2, $rawProducts);
        $this->assertSame('sku-1', $rawProducts[0]->external_id);
        $this->assertSame('Mixer 500', $rawProducts[0]->raw_title);
        $this->assertSame('Acme', $rawProducts[0]->raw_brand);
        $this->assertSame('Mixers', $rawProducts[0]->raw_category);
        $this->assertSame(['Power' => '500 W'], $rawProducts[0]->raw_payload_json['specifications']);
        $this->assertSame('Kettle Pro', $rawProducts[1]->raw_title);
        $this->assertSame(2, $batch->fresh()->total_items);
        $this->assertSame(2, $batch->raw_items_count);
        $this->assertSame(0, CentralProduct::query()->count());
    }

    public function test_invalid_serialized_payload_becomes_import_error_without_throwing(): void
    {
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
        ]);
        $batch = ImportBatch::factory()->for($source, 'source')->create();
        $file = UploadedFile::fake()->createWithContent('broken.phpdata', 'not serialized data');

        (new SerializedPhpProductImporter)->import($batch, $file);

        $error = NormalizationError::query()->sole();
        $this->assertSame('invalid_serialized_payload', $error->code);
        $this->assertSame('error', $error->severity);
        $this->assertSame(1, $batch->fresh()->failed_count);
        $this->assertSame(0, $batch->raw_items_count);
        $this->assertSame(0, RawProduct::query()->count());
    }

    public function test_recursive_serialized_payload_is_rejected_before_product_traversal(): void
    {
        $batch = ImportBatch::factory()->create();
        $product = ['title' => 'Recursive'];
        $product['self'] = &$product;
        $file = UploadedFile::fake()->createWithContent('recursive.phpdata', serialize([$product]));

        (new SerializedPhpProductImporter)->import($batch, $file);

        $this->assertSame('invalid_serialized_payload', $batch->errors()->sole()->code);
        $this->assertSame(1, $batch->fresh()->failed_count);
        $this->assertSame(0, RawProduct::query()->count());
    }

    public function test_refuses_to_read_more_than_the_configured_artifact_limit(): void
    {
        config()->set('imports.serialized_php_max_bytes', 8);
        $batch = ImportBatch::factory()->create();
        $file = UploadedFile::fake()->createWithContent('large.phpdata', serialize([['id' => 1]]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('exceeds the configured size limit');

        (new SerializedPhpProductImporter)->import($batch, $file);
    }

    public function test_rejects_serialized_payload_beyond_the_configured_depth(): void
    {
        config()->set('imports.serialized_php_max_depth', 3);
        $batch = ImportBatch::factory()->create();
        $file = UploadedFile::fake()->createWithContent('deep.phpdata', serialize([
            ['specs' => ['nested' => ['too' => ['deep' => true]]]],
        ]));

        (new SerializedPhpProductImporter)->import($batch, $file);

        $this->assertSame('invalid_serialized_payload', $batch->errors()->sole()->code);
        $this->assertSame(1, $batch->fresh()->failed_count);
        $this->assertSame(0, RawProduct::query()->count());
    }
}
