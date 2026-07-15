<?php

namespace Tests\Feature\Content;

use App\Enums\ContentRelationTargetType;
use App\Filament\Resources\ContentItemResource\Pages\EditContentItem;
use App\Filament\Resources\ContentItemResource\RelationManagers\RelationsRelationManager;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\ContentItem;
use App\Models\ContentRelation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ContentBrandRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_can_be_related_to_brand(): void
    {
        $item = ContentItem::factory()->create();
        $brand = CentralBrand::factory()->create();

        $relation = ContentRelation::factory()->for($item)->brand($brand)->create();

        $this->assertSame(ContentRelationTargetType::Brand, $relation->related_type);
        $this->assertSame($brand->id, $relation->related_id);
    }

    public function test_duplicate_brand_relation_is_prevented(): void
    {
        $item = ContentItem::factory()->create();
        $brand = CentralBrand::factory()->create();
        ContentRelation::factory()->for($item)->brand($brand)->create();

        $this->expectException(QueryException::class);

        ContentRelation::factory()->for($item)->brand($brand)->create();
    }

    public function test_brand_relation_requires_existing_brand(): void
    {
        $this->expectException(ValidationException::class);

        ContentRelation::factory()->create([
            'related_type' => ContentRelationTargetType::Brand,
            'related_id' => 999999,
        ]);
    }

    public function test_admin_can_add_brand_relation(): void
    {
        $site = Site::factory()->create();
        $item = ContentItem::factory()->for($site)->create();
        $brand = CentralBrand::factory()->create();

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(RelationsRelationManager::class, [
                'ownerRecord' => $item,
                'pageClass' => EditContentItem::class,
            ])
            ->callTableAction('create', data: [
                'related_type' => ContentRelationTargetType::Brand->value,
                'related_id' => $brand->id,
                'relation_type' => 'featured',
                'position' => 5,
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('content_relations', [
            'content_item_id' => $item->id,
            'related_type' => ContentRelationTargetType::Brand->value,
            'related_id' => $brand->id,
        ]);
    }
}
