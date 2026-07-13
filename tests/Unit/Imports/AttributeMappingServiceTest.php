<?php

namespace Tests\Unit\Imports;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\RawProduct;
use App\Services\Imports\AttributeMappingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeMappingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_reviewed_mapping_by_normalized_source_key(): void
    {
        $source = ImportSource::factory()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->for($category, 'category')->create();
        AttributeMapping::query()->create([
            'import_source_id' => $source->id,
            'category_id' => $category->id,
            'raw_key' => 'Power (W)',
            'normalized_raw_key' => 'power_w',
            'attribute_definition_id' => $definition->id,
            'confidence' => '1.0000',
            'status' => 'reviewed',
            'mapping_type' => 'attribute',
        ]);

        $resolved = (new AttributeMappingService)->resolve(
            $source->id,
            $category->id,
            '  POWER - W  '
        );

        $this->assertTrue($definition->is($resolved));
    }

    public function test_records_missing_mapping_without_creating_attribute_definition(): void
    {
        $source = ImportSource::factory()->create();
        $category = CentralCategory::factory()->create();
        $service = new AttributeMappingService;
        $definitionsBefore = AttributeDefinition::query()->count();

        $this->assertNull($service->resolve($source->id, $category->id, 'Unknown Field'));

        $mapping = $service->recordUnmapped($source->id, $category->id, ' Unknown   Field ');

        $this->assertNull($mapping->attribute_definition_id);
        $this->assertSame('unknown_field', $mapping->normalized_raw_key);
        $this->assertSame('auto', $mapping->status);
        $this->assertSame($definitionsBefore, AttributeDefinition::query()->count());
    }

    public function test_does_not_resolve_ambiguous_normalized_key_mapping(): void
    {
        $source = ImportSource::factory()->create();
        $category = CentralCategory::factory()->create();

        foreach (['Power (W)', 'Power-W'] as $rawKey) {
            AttributeMapping::query()->create([
                'import_source_id' => $source->id,
                'category_id' => $category->id,
                'raw_key' => $rawKey,
                'normalized_raw_key' => 'power_w',
                'attribute_definition_id' => AttributeDefinition::factory()->for($category, 'category')->create()->id,
                'confidence' => '1.0000',
                'status' => 'reviewed',
                'mapping_type' => 'attribute',
            ]);
        }

        $service = new AttributeMappingService;

        $this->assertNull($service->resolve($source->id, $category->id, 'POWER / W'));
        $this->assertNotNull($service->resolve($source->id, $category->id, 'Power (W)'));
    }

    public function test_counts_source_payloads_containing_the_raw_key_in_the_database(): void
    {
        $source = ImportSource::factory()->create();
        $otherSource = ImportSource::factory()->create();
        $category = CentralCategory::factory()->create();
        $mapping = AttributeMapping::query()->create([
            'import_source_id' => $source->id,
            'category_id' => $category->id,
            'raw_key' => 'Power (W)',
            'normalized_raw_key' => 'power_w',
            'confidence' => '1.0000',
            'status' => 'reviewed',
            'mapping_type' => 'attribute',
        ]);
        $batch = ImportBatch::factory()->create(['import_source_id' => $source->id]);
        $otherBatch = ImportBatch::factory()->create(['import_source_id' => $otherSource->id]);

        RawProduct::factory()->create([
            'import_batch_id' => $batch->id,
            'import_source_id' => $source->id,
            'raw_payload_json' => ['Power (W)' => 500],
        ]);
        RawProduct::factory()->create([
            'import_batch_id' => $batch->id,
            'import_source_id' => $source->id,
            'raw_payload_json' => ['POWER - W' => 300],
        ]);
        RawProduct::factory()->create([
            'import_batch_id' => $otherBatch->id,
            'import_source_id' => $otherSource->id,
            'raw_payload_json' => ['Power (W)' => 700],
        ]);

        $this->assertSame(2, (new AttributeMappingService)->usageCount($mapping));
    }
}
