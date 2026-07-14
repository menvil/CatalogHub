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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $attribute = AttributeDefinition::factory()->for($category, 'category')->create([
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
}
