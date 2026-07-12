<?php

namespace Tests\Unit\Imports;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportSource;
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
}
