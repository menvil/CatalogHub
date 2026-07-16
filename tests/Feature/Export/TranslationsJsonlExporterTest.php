<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Services\Export\TranslationsJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TranslationsJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_multiple_translation_tables_in_a_unified_jsonl_format(): void
    {
        Storage::fake('local');
        ProductTranslation::factory()->count(2)->create();
        CategoryTranslation::factory()->create();
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(TranslationsJsonlExporter::class)->export($snapshot);
        $rows = collect(explode("\n", trim(Storage::disk($result->disk)->get($result->path))))
            ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame(3, $result->lineCount);
        $this->assertEqualsCanonicalizing(['category', 'product'], $rows->pluck('entity_type')->unique()->values()->all());

        foreach ($rows as $row) {
            $this->assertArrayHasKey('entity_id', $row);
            $this->assertArrayHasKey('locale', $row);
            $this->assertSame('fields', $row['field']);
            $this->assertIsArray($row['value']);
            $this->assertArrayHasKey('status', $row);
            $this->assertArrayHasKey('source_hash', $row);
        }

        $this->assertSame(3, $snapshot->fresh()->files_json['translations']['line_count']);
    }
}
