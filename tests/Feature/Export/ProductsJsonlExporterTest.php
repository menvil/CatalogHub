<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralProduct;
use App\Services\Export\ProductsJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductsJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_streams_products_to_jsonl_and_records_integrity_metadata(): void
    {
        Storage::fake('local');
        CentralProduct::factory()->count(2)->create();
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(ProductsJsonlExporter::class)->export($snapshot);

        Storage::disk($result->disk)->assertExists($result->path);
        $content = Storage::disk($result->disk)->get($result->path);
        $lines = explode("\n", trim($content));

        $this->assertSame(2, $result->lineCount);
        $this->assertCount(2, $lines);
        $this->assertIsArray(json_decode($lines[0], true, flags: JSON_THROW_ON_ERROR));
        $this->assertSame(hash('sha256', $content), $result->checksum);
        $this->assertSame(strlen($content), $result->fileSize);

        $file = $snapshot->fresh()->files_json['products'];
        $this->assertSame($result->path, $file['path']);
        $this->assertSame(2, $file['line_count']);
        $this->assertSame($result->checksum, $file['checksum']);
    }

    public function test_product_export_contains_only_portable_catalog_fields(): void
    {
        Storage::fake('local');
        $product = CentralProduct::factory()->create([
            'name' => 'Portable Product',
            'model' => 'PP-1',
            'version' => 4,
        ]);
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(ProductsJsonlExporter::class)->export($snapshot);
        $row = json_decode(
            trim(Storage::disk($result->disk)->get($result->path)),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $this->assertSame($product->id, $row['id']);
        $this->assertSame('Portable Product', $row['name']);
        $this->assertSame('PP-1', $row['model']);
        $this->assertSame(4, $row['version']);
        $this->assertSame($product->central_brand_id, $row['brand_id']);
        $this->assertArrayNotHasKey('password', $row);
        $this->assertArrayNotHasKey('credentials', $row);
    }
}
