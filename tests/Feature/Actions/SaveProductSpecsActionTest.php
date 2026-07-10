<?php

namespace Tests\Feature\Actions;

use App\Actions\ProductAttributes\SaveProductSpecsAction;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveProductSpecsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_validates_product_specs_payload_through_action(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'refresh_rate',
                'data_type' => 'decimal',
            ]);

        $validated = app(SaveProductSpecsAction::class)->handle($product, [
            $attribute->id => ['value_number' => 165],
        ]);

        $this->assertArrayHasKey($attribute->id, $validated);
        $this->assertSame(165, $validated[$attribute->id]['value_number']);
    }
}
