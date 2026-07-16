<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\Export\CategoriesJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoriesJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_category_hierarchy_and_schema_status_to_jsonl(): void
    {
        Storage::fake('local');
        $parent = CentralCategory::factory()->create(['name' => 'Parent']);
        CentralCategory::factory()->for($parent, 'parent')->create(['name' => 'Child']);
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(CategoriesJsonlExporter::class)->export($snapshot);
        $rows = collect(explode("\n", trim(Storage::disk($result->disk)->get($result->path))))
            ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame(2, $result->lineCount);
        $this->assertSame($parent->id, $rows->firstWhere('name', 'Child')['parent_id']);
        $this->assertArrayHasKey('schema_status', $rows->first());
        $this->assertSame(2, $snapshot->fresh()->files_json['categories']['line_count']);
    }
}
