<?php

namespace Tests\Feature\Filament;

use App\Enums\UserRole;
use App\Filament\Resources\CentralProductResource;
use App\Filament\Resources\CentralProductResource\Pages\ProductSpecsEditor;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSpecsEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_product_resource_registers_specs_editor_page(): void
    {
        $pages = CentralProductResource::getPages();

        $this->assertArrayHasKey('specs', $pages);
        $this->assertTrue(class_exists(ProductSpecsEditor::class));
    }

    public function test_guest_is_redirected_from_product_specs_editor(): void
    {
        $product = CentralProduct::factory()->create();

        $this->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertRedirect('/admin/login');
    }

    public function test_allows_central_admin_to_open_product_specs_editor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $product = CentralProduct::factory()->for($category, 'category')->create([
            'name' => 'LG UltraGear 27GP850-B',
        ]);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('Product Specs')
            ->assertSee('LG UltraGear 27GP850-B')
            ->assertSee('Monitors');
    }

    public function test_product_specs_editor_shows_empty_state_without_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $product = CentralProduct::factory()->create(['name' => 'Uncategorized product']);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('Choose a category first')
            ->assertSee('Uncategorized product');
    }

    public function test_product_specs_editor_displays_attributes_grouped_by_category_sections(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $display = AttributeSection::factory()->for($category, 'category')->create([
            'name' => 'Display',
            'code' => 'display',
            'position' => 1,
        ]);
        $ports = AttributeSection::factory()->for($category, 'category')->create([
            'name' => 'Ports',
            'code' => 'ports',
            'position' => 2,
        ]);

        $refreshRate = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($display, 'section')
            ->create([
                'name' => 'Refresh rate',
                'code' => 'refresh_rate',
                'data_type' => 'decimal',
                'position' => 1,
            ]);

        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($ports, 'section')
            ->create([
                'name' => 'USB-C',
                'code' => 'usb_c',
                'data_type' => 'boolean',
                'position' => 1,
            ]);

        $product = CentralProduct::factory()->for($category, 'category')->create();
        CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->for($refreshRate, 'attributeDefinition')
            ->create(['raw_value' => '165 Hz']);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('Display')
            ->assertSee('display')
            ->assertSee('Refresh rate')
            ->assertSee('refresh_rate')
            ->assertSee('165 Hz')
            ->assertSee('Ports')
            ->assertSee('usb_c');
    }

    public function test_product_specs_editor_renders_numeric_input_for_numeric_attribute(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();

        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Refresh rate',
                'code' => 'refresh_rate',
                'data_type' => 'decimal',
            ]);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('refresh_rate')
            ->assertSeeHtml('type="number"')
            ->assertSeeHtml('step="any"');
    }

    public function test_product_specs_editor_renders_text_input_for_string_attribute(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();

        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Model name',
                'code' => 'model_name',
                'data_type' => 'string',
            ]);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('model_name')
            ->assertSeeHtml('type="text"')
            ->assertSeeHtml('value_text');
    }

    public function test_product_specs_editor_renders_boolean_control_for_boolean_attribute(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();

        AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Has USB-C',
                'code' => 'has_usb_c',
                'data_type' => 'boolean',
            ]);

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('has_usb_c')
            ->assertSee('Unknown')
            ->assertSee('Yes')
            ->assertSee('No')
            ->assertSeeHtml('value_bool');
    }

    public function test_product_specs_editor_renders_select_with_attribute_options_for_enum_attribute(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Panel type',
                'code' => 'panel_type',
                'data_type' => 'enum',
            ]);

        foreach (['ips', 'va', 'oled'] as $position => $code) {
            AttributeOption::factory()->for($attribute, 'attribute')->create([
                'code' => $code,
                'label' => $code,
                'position' => $position,
            ]);
        }

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('panel_type')
            ->assertSee('ips')
            ->assertSee('va')
            ->assertSee('oled')
            ->assertSeeHtml('value_enum_code');
    }

    public function test_product_specs_editor_renders_checkbox_group_for_multi_enum_attribute(): void
    {
        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Ports',
                'code' => 'ports',
                'data_type' => 'multi_enum',
            ]);

        foreach (['hdmi', 'displayport', 'usb_c'] as $position => $code) {
            AttributeOption::factory()->for($attribute, 'attribute')->create([
                'code' => $code,
                'label' => $code,
                'position' => $position,
            ]);
        }

        $this->actingAs($admin)
            ->get(ProductSpecsEditor::getUrl(['record' => $product]))
            ->assertOk()
            ->assertSee('ports')
            ->assertSee('hdmi')
            ->assertSee('displayport')
            ->assertSee('usb_c')
            ->assertSeeHtml('type="checkbox"')
            ->assertSeeHtml('value_json');
    }
}
