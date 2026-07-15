<?php

namespace Tests\Feature\Content;

use App\Enums\ContentRelationTargetType;
use App\Filament\Resources\ContentItemResource\Pages\EditContentItem;
use App\Filament\Resources\ContentItemResource\RelationManagers\RelationsRelationManager;
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

class ContentCategoryRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_can_be_related_to_category(): void
    {
        $item = ContentItem::factory()->create();
        $category = CentralCategory::factory()->create();

        $relation = ContentRelation::factory()->for($item)->category($category)->create();

        $this->assertSame(ContentRelationTargetType::Category, $relation->related_type);
        $this->assertSame($category->id, $relation->related_id);
    }

    public function test_duplicate_category_relation_is_prevented(): void
    {
        $item = ContentItem::factory()->create();
        $category = CentralCategory::factory()->create();
        ContentRelation::factory()->for($item)->category($category)->create();

        $this->expectException(QueryException::class);

        ContentRelation::factory()->for($item)->category($category)->create();
    }

    public function test_category_relation_requires_existing_category(): void
    {
        $this->expectException(ValidationException::class);

        ContentRelation::factory()->create([
            'related_type' => ContentRelationTargetType::Category,
            'related_id' => 999999,
        ]);
    }

    public function test_admin_can_add_category_relation(): void
    {
        $site = Site::factory()->create();
        $item = ContentItem::factory()->for($site)->create();
        $category = CentralCategory::factory()->create();

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(RelationsRelationManager::class, [
                'ownerRecord' => $item,
                'pageClass' => EditContentItem::class,
            ])
            ->callTableAction('create', data: [
                'related_type' => ContentRelationTargetType::Category->value,
                'related_id' => $category->id,
                'relation_type' => 'related',
                'position' => 10,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('content_relations', [
            'content_item_id' => $item->id,
            'related_type' => ContentRelationTargetType::Category->value,
            'related_id' => $category->id,
            'position' => 10,
        ]);
    }
}
