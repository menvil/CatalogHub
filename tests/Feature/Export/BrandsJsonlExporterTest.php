<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralBrand;
use App\Services\Export\BrandsJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BrandsJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_portable_brand_identity_to_jsonl(): void
    {
        Storage::fake('local');
        CentralBrand::factory()->count(2)->create();
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(BrandsJsonlExporter::class)->export($snapshot);
        $lines = explode("\n", trim(Storage::disk($result->disk)->get($result->path)));
        $first = json_decode($lines[0], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(2, $result->lineCount);
        $this->assertCount(2, $lines);
        $this->assertArrayHasKey('slug', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('status', $first);
        $this->assertSame(2, $snapshot->fresh()->files_json['brands']['line_count']);
    }
}
