<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Services\Export\AttributeValuesJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttributeValuesJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_raw_typed_and_canonical_attribute_values(): void
    {
        Storage::fake('local');
        CentralProductAttributeValue::factory()->create([
            'raw_value' => '144 Hz',
            'value_type' => 'decimal',
            'value_number' => 144,
            'source_unit' => 'Hz',
            'canonical_value' => 144,
            'canonical_unit' => 'Hz',
            'confidence' => 0.95,
            'source_type' => 'manufacturer',
            'source_id' => 'spec-sheet',
        ]);
        CentralProductAttributeValue::factory()->create([
            'raw_value' => '["HDMI","DisplayPort"]',
            'value_type' => 'json',
            'value_json' => ['HDMI', 'DisplayPort'],
        ]);
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(AttributeValuesJsonlExporter::class)->export($snapshot);
        $rows = collect(explode("\n", trim(Storage::disk($result->disk)->get($result->path))))
            ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame(2, $result->lineCount);
        $this->assertSame('144 Hz', $rows[0]['raw_value']);
        $this->assertSame('144.000000', $rows[0]['canonical_value']);
        $this->assertSame(['HDMI', 'DisplayPort'], $rows[1]['value_json']);
        $this->assertSame(2, $snapshot->fresh()->files_json['attribute_values']['line_count']);
    }
}
