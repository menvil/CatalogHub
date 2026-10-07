<?php

namespace Tests\Feature\Content;

use App\Enums\ContentRelationTargetType;
use App\Filament\Resources\ContentItemResource\Pages\EditContentItem;
use App\Filament\Resources\ContentItemResource\RelationManagers\RelationsRelationManager;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\ContentItem;
use App\Models\ContentRelation;
use App\Models\Site;
use App\Models\User;
use Filament\Forms\Components\Select;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ContentAttributeRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_can_be_related_to_attribute_definition(): void
    {
        $item = ContentItem::factory()->create();
        $attribute = AttributeDefinition::factory()->create();

        $relation = ContentRelation::factory()->for($item)->attribute($attribute)->create();

        $this->assertSame(ContentRelationTargetType::Attribute, $relation->related_type);
        $this->assertSame($attribute->id, $relation->related_id);
    }

    public function test_duplicate_attribute_relation_is_prevented(): void
    {
        $item = ContentItem::factory()->create();
        $attribute = AttributeDefinition::factory()->create();
        ContentRelation::factory()->for($item)->attribute($attribute)->create();

        $this->expectException(QueryException::class);

        ContentRelation::factory()->for($item)->attribute($attribute)->create();
    }

    public function test_attribute_relation_requires_existing_definition(): void
    {
        $this->expectException(ValidationException::class);

        ContentRelation::factory()->create([
            'related_type' => ContentRelationTargetType::Attribute,
            'related_id' => 999999,
        ]);
    }

    public function test_admin_can_add_attribute_relation(): void
    {
        $site = Site::factory()->create();
        $item = ContentItem::factory()->for($site)->create();
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $attribute = AttributeDefinition::factory()->assignedTo($category)->create([
            'name' => 'Refresh rate',
        ]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(RelationsRelationManager::class, [
                'ownerRecord' => $item,
                'pageClass' => EditContentItem::class,
            ])
            ->callTableAction('create', data: [
                'related_type' => ContentRelationTargetType::Attribute->value,
                'related_id' => $attribute->id,
                'relation_type' => 'related',
                'position' => 0,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('content_relations', [
            'content_item_id' => $item->id,
            'related_type' => ContentRelationTargetType::Attribute->value,
            'related_id' => $attribute->id,
        ]);
    }

    public function test_attribute_select_preloads_global_options_on_the_contracted_schema_and_creates_relation(): void
    {
        self::assertFalse(Schema::hasColumn('attribute_definitions', 'central_category_id'));
        self::assertFalse(Schema::hasColumn('attribute_definitions', 'position'));
        $site = Site::factory()->create();
        $item = ContentItem::factory()->for($site)->create();
        $z = AttributeDefinition::factory()->create(['name' => 'Z global', 'code' => 'z_global']);
        $a = AttributeDefinition::factory()->create(['name' => 'A global', 'code' => 'a_global']);
        $same = AttributeDefinition::factory()->create(['name' => 'A global', 'code' => 'b_global']);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(RelationsRelationManager::class, ['ownerRecord' => $item, 'pageClass' => EditContentItem::class])
            ->mountTableAction('create')
            ->fillForm(['related_type' => ContentRelationTargetType::Attribute->value])
            ->assertFormFieldExists('related_id', function (Select $select) use ($a, $same, $z): bool {
                self::assertTrue($select->isPreloaded());
                self::assertSame([$a->id => 'A global (a_global)', $same->id => 'A global (b_global)', $z->id => 'Z global (z_global)'], $select->getOptions());
                self::assertCount(3, $select->getOptionsForJs());

                return true;
            })
            ->fillForm(['related_type' => ContentRelationTargetType::Attribute->value, 'related_id' => $a->id, 'relation_type' => 'related', 'position' => 0])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('content_relations', ['content_item_id' => $item->id, 'related_type' => 'attribute', 'related_id' => $a->id]);
    }
}
