<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\CategoryProjectionBuilder;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteOverride;
use App\Models\Translations\CategoryTranslation;
use Database\Seeders\MeasurementDimensionsSeeder;
use Database\Seeders\MetricMeasurementUnitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryProjectionBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_deterministic_localized_category_projection(): void
    {
        $this->seed([MeasurementDimensionsSeeder::class, MetricMeasurementUnitsSeeder::class]);
        $locale = Locale::factory()->create(['code' => 'de-DE', 'is_default' => true]);
        $site = Site::factory()->create(['domain' => 'catalog.example']);
        $parent = CentralCategory::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => CentralCategoryStatus::Active,
        ]);
        $category = CentralCategory::factory()->for($parent, 'parent')->create([
            'name' => 'Monitors',
            'slug' => 'monitors',
            'status' => CentralCategoryStatus::Active,
            'schema_status' => CategorySchemaStatus::Approved,
        ]);
        $child = CentralCategory::factory()->for($category, 'parent')->create([
            'name' => 'Gaming Monitors',
            'slug' => 'gaming-monitors',
            'status' => CentralCategoryStatus::Active,
        ]);
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $refreshRate = AttributeDefinition::factory()->withCanonicalUnit('hertz')
            ->assignedTo($category)
            ->state(['attribute_section_id' => $section->id])
            ->create([
                'code' => 'refresh_rate',
                'name' => 'Refresh rate',
                'data_type' => 'integer',
            ]);

        FacetDefinition::factory()->create(['category_id' => $category->id, 'source_type' => 'attribute', 'category_attribute_assignment_id' => $refreshRate->assignments()->sole()->id, 'code' => 'refresh_filter', 'facet_type' => 'range']);
        CategoryComparisonAttribute::query()->create(['central_category_id' => $category->id, 'category_attribute_assignment_id' => $refreshRate->assignments()->sole()->id]);

        CategoryTranslation::factory()->create([
            'category_id' => $category->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Monitore',
            'seo_title' => 'Monitore kaufen',
        ]);
        SiteOverride::create([
            'site_id' => $site->id,
            'entity_type' => 'category',
            'entity_id' => $category->id,
            'field' => 'local_slug',
            'locale_code' => 'de-DE',
            'value_json' => ['value' => 'monitore'],
            'status' => 'active',
        ]);

        $builder = app(CategoryProjectionBuilder::class);
        $first = $builder->build($site, $category, 'de-DE');
        $second = $builder->build($site, $category, 'de-DE');

        $this->assertSame($category->id, $first->payload['category']['id']);
        $this->assertSame('Monitore', $first->payload['category']['title']);
        $this->assertSame('monitore', $first->slug);
        $this->assertSame($parent->id, $first->payload['parent']['id']);
        $this->assertSame($child->id, $first->payload['children'][0]['id']);
        $this->assertSame($refreshRate->assignments()->sole()->id, $first->facets[0]['assignment_id']);
        $this->assertSame('refresh_rate', $first->comparison[0]['code']);
        $this->assertSame('Monitore kaufen', $first->seo['meta_title']);
        $this->assertSame('https://catalog.example/categories/monitore', $first->seo['canonical_url']);
        $this->assertSame(ProjectionStatus::Active, $first->status);
        $this->assertSame($first->checksum, $second->checksum);
    }
}
