<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\Export\AttributesJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttributesJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_sections_definitions_and_options_with_distinct_entity_types(): void
    {
        Storage::fake('local');
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->create(['central_category_id' => $category->id]);
        $definition = AttributeDefinition::factory()->create([
            'central_category_id' => $category->id,
            'attribute_section_id' => $section->id,
            'is_filterable' => true,
            'is_comparable' => true,
        ]);
        AttributeOption::factory()->create(['attribute_definition_id' => $definition->id]);
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(AttributesJsonlExporter::class)->export($snapshot);
        $rows = collect(explode("\n", trim(Storage::disk($result->disk)->get($result->path))))
            ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame(3, $result->lineCount);
        $this->assertSame([
            'attribute_section',
            'attribute_definition',
            'attribute_option',
        ], $rows->pluck('entity_type')->all());
        $this->assertTrue($rows->firstWhere('entity_type', 'attribute_definition')['flags']['is_filterable']);
        $this->assertSame(3, $snapshot->fresh()->files_json['attributes']['line_count']);
    }
}
