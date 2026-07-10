<?php

namespace Tests\Feature\Services;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Services\ProductAttributes\MissingRequiredAttributesResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingRequiredAttributesResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_required_attributes_without_values(): void
    {
        [$product, $missing] = $this->productWithRequiredAttribute('refresh_rate');

        $result = app(MissingRequiredAttributesResolver::class)->resolve($product);

        $this->assertCount(1, $result);
        $this->assertSame($missing->id, $result[0]->id);
    }

    public function test_omits_required_attributes_with_typed_values(): void
    {
        [$product, $attribute] = $this->productWithRequiredAttribute('refresh_rate');
        CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->for($attribute, 'attributeDefinition')
            ->create([
                'value_type' => 'decimal',
                'value_number' => 165,
            ]);

        $result = app(MissingRequiredAttributesResolver::class)->resolve($product);

        $this->assertSame([], $result);
    }

    /**
     * @return array{CentralProduct, AttributeDefinition}
     */
    private function productWithRequiredAttribute(string $code): array
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => $code,
                'data_type' => 'decimal',
                'is_required' => true,
            ]);

        return [$product, $attribute];
    }
}
