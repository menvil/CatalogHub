<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProductProjection;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComparePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_compare_renders_two_same_category_projections_side_by_side(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $monitors = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $this->projection($site, $monitors, 'aurora', 'Aurora 27', '3840 × 2160');
        $this->projection($site, $monitors, 'horizon', 'Horizon 32', '2560 × 1440');

        $this->get('http://tech-compare.test/en-US/compare?products%5B0%5D=aurora&products%5B1%5D=horizon')
            ->assertOk()
            ->assertSee('Aurora 27')
            ->assertSee('Horizon 32')
            ->assertSee('3840 × 2160')
            ->assertSee('2560 × 1440')
            ->assertSee('Resolution');
    }

    public function test_compare_blocks_different_categories(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $monitors = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $keyboards = CentralCategory::query()->where('slug', 'keyboards')->firstOrFail();
        $this->projection($site, $monitors, 'aurora', 'Aurora 27', '4K');
        $this->projection($site, $keyboards, 'keystone', 'Keystone TKL', 'TKL');

        $this->get('http://tech-compare.test/en-US/compare?products=aurora,keystone')
            ->assertOk()
            ->assertSee('Products must belong to the same category')
            ->assertDontSee('data-comparison-table', false);
    }

    public function test_compare_blocks_products_with_a_missing_category_id(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $monitors = CentralCategory::query()->where('slug', 'monitors')->firstOrFail();
        $this->projection($site, $monitors, 'aurora', 'Aurora 27', '4K');
        $projection = $this->projection($site, $monitors, 'horizon', 'Horizon 32', 'QHD');
        $projection->update([
            'payload_json' => [
                'spec_sections' => data_get($projection->payload_json, 'spec_sections'),
            ],
        ]);

        $this->get('http://tech-compare.test/en-US/compare?products=aurora,horizon')
            ->assertOk()
            ->assertSee('Products must belong to the same category')
            ->assertDontSee('data-comparison-table', false);
    }

    private function projection(
        Site $site,
        CentralCategory $category,
        string $slug,
        string $title,
        string $displayValue,
    ): SiteProductProjection {
        $definition = AttributeDefinition::query()->where('code', 'resolution')->first()
            ?? AttributeDefinition::factory()->create(['code' => 'resolution', 'name' => 'Resolution', 'data_type' => 'string']);
        $assignment = CategoryAttributeAssignment::query()->firstOrCreate(['central_category_id' => $category->id, 'attribute_definition_id' => $definition->id]);
        $comparison = CategoryComparisonAttribute::query()->firstOrCreate(['central_category_id' => $category->id, 'category_attribute_assignment_id' => $assignment->id]);
        $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);

        return SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => $slug,
            'title' => $title,
            'status' => ProjectionStatus::Active,
            'payload_json' => [
                'schema_version' => 1,
                'comparison' => [['category_attribute_assignment_id' => $assignment->id, 'position' => $comparison->fresh()->position]],
                'attributes' => [['assignment_id' => $assignment->id, 'definition_id' => $definition->id, 'data_type' => 'string', 'has_value' => true, 'canonical_value' => $displayValue, 'display_value' => $displayValue, 'measurement_dimension_id' => null, 'canonical_measurement_unit_id' => null, 'label' => 'Resolution', 'section' => ['id' => null, 'label' => 'Ungrouped']]],
                'category' => ['id' => $category->id, 'label' => $category->name, 'slug' => $category->slug],
                'spec_sections' => [[
                    'code' => 'display',
                    'label' => 'Display',
                    'attributes' => [['code' => 'resolution', 'label' => 'Resolution', 'display_value' => $displayValue]],
                ]],
            ],
            'media_json' => [],
        ]);
    }
}
