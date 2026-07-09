<?php

namespace Tests\Feature\Actions;

use App\Actions\CategorySchema\CreateAttributeOptionAction;
use App\Actions\CategorySchema\DeleteAttributeOptionAction;
use App\Actions\CategorySchema\UpdateAttributeOptionAction;
use App\Enums\AttributeDataType;
use App\Exceptions\CategorySchema\CannotManageAttributeOptionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AttributeOptionActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_option_for_enum_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Enum,
        ]);

        $option = app(CreateAttributeOptionAction::class)->handle($attribute, [
            'code' => 'ips',
            'label' => 'IPS',
        ]);

        $this->assertTrue($option->attribute->is($attribute));
        $this->assertSame('ips', $option->code);
        $this->assertSame(1, $option->position);
    }

    public function test_creates_option_for_multi_enum_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::MultiEnum,
        ]);

        $option = app(CreateAttributeOptionAction::class)->handle($attribute, [
            'code' => 'red',
            'label' => 'Red',
        ]);

        $this->assertTrue($option->attribute->is($attribute));
    }

    public function test_does_not_create_option_for_non_enum_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Decimal,
        ]);

        $this->expectException(CannotManageAttributeOptionException::class);

        app(CreateAttributeOptionAction::class)->handle($attribute, [
            'code' => 'ips',
            'label' => 'IPS',
        ]);
    }

    public function test_rejects_duplicate_option_code_inside_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Enum,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create(['code' => 'ips']);

        $this->expectException(ValidationException::class);

        app(CreateAttributeOptionAction::class)->handle($attribute, [
            'code' => 'ips',
            'label' => 'IPS',
        ]);
    }

    public function test_updates_option_for_enum_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Enum,
        ]);
        $option = AttributeOption::factory()->for($attribute, 'attribute')->create([
            'code' => 'old',
            'label' => 'Old',
            'position' => 1,
            'is_visible' => true,
        ]);

        app(UpdateAttributeOptionAction::class)->handle($option, [
            'code' => 'ips',
            'label' => 'IPS',
            'position' => 3,
            'is_visible' => false,
        ]);

        $option->refresh();

        $this->assertSame('ips', $option->code);
        $this->assertSame('IPS', $option->label);
        $this->assertSame(3, $option->position);
        $this->assertFalse($option->is_visible);
    }

    public function test_deletes_option_for_enum_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Enum,
        ]);
        $option = AttributeOption::factory()->for($attribute, 'attribute')->create();

        app(DeleteAttributeOptionAction::class)->handle($option);

        $this->assertDatabaseMissing('attribute_options', ['id' => $option->id]);
    }

    public function test_orders_attribute_options_by_position(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'data_type' => AttributeDataType::Enum,
        ]);
        AttributeOption::factory()->for($attribute, 'attribute')->create(['code' => 'b', 'position' => 2]);
        AttributeOption::factory()->for($attribute, 'attribute')->create(['code' => 'a', 'position' => 1]);

        $codes = $attribute->options()->ordered()->pluck('code')->all();

        $this->assertSame(['a', 'b'], $codes);
    }
}
