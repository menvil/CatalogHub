<?php

namespace Tests\Feature\Smoke;

use App\Importers\SerializedPhpProductImporter;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\RawProduct;
use App\Services\Imports\ImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('smoke')]
class ImportSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_fixture_reaches_pending_raw_product_without_network_access(): void
    {
        Storage::fake('imports');
        $source = ImportSource::factory()->create([
            'type' => ImportSource::TYPE_SERIALIZED_PHP,
            'status' => 'active',
        ]);

        $batch = (new ImportService([new SerializedPhpProductImporter]))->startImport(
            $source,
            base_path('tests/Fixtures/imports/smoke-product.phpdata'),
        );

        $rawProduct = RawProduct::query()->sole();

        $this->assertInstanceOf(ImportBatch::class, $batch);
        $this->assertSame($source->id, $batch->import_source_id);
        $this->assertSame('completed', $batch->status);
        $this->assertSame(1, $batch->raw_items_count);
        $this->assertSame('pending', $rawProduct->status);
        $this->assertSame('smoke-1', $rawProduct->external_id);
        $this->assertSame('Smoke Monitor', $rawProduct->raw_title);
        $this->assertSame('165 Hz', $rawProduct->raw_payload_json['specifications']['refresh_rate']);
        $this->assertSame(1, $batch->artifacts()->where('type', 'original')->count());
    }
}
