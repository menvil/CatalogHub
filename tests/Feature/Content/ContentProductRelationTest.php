<?php

namespace Tests\Feature\Content;

use App\Enums\ContentRelationTargetType;
use App\Filament\Resources\ContentItemResource;
use App\Filament\Resources\ContentItemResource\Pages\EditContentItem;
use App\Filament\Resources\ContentItemResource\RelationManagers\RelationsRelationManager;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ContentItem;
use App\Models\ContentRelation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ContentProductRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_can_be_related_to_product(): void
    {
        $item = ContentItem::factory()->create();
        $product = CentralProduct::factory()->create();

        $relation = ContentRelation::factory()->for($item)->product($product)->create();

        $this->assertSame(ContentRelationTargetType::Product, $relation->related_type);
        $this->assertSame($product->id, $relation->related_id);
        $this->assertTrue($relation->contentItem->is($item));
    }

    public function test_duplicate_product_relation_is_prevented(): void
    {
        $item = ContentItem::factory()->create();
        $product = CentralProduct::factory()->create();
        ContentRelation::factory()->for($item)->product($product)->create();

        $this->expectException(QueryException::class);

        ContentRelation::factory()->for($item)->product($product)->create();
    }

    public function test_product_relation_requires_existing_product(): void
    {
        $this->expectException(ValidationException::class);

        ContentRelation::factory()->create([
            'related_type' => ContentRelationTargetType::Product,
            'related_id' => 999999,
        ]);
    }

    public function test_updating_non_target_fields_does_not_revalidate_the_target(): void
    {
        $product = CentralProduct::factory()->create();
        $relation = ContentRelation::factory()->product($product)->create();
        $product->delete();

        $relation->update(['position' => 10]);

        $this->assertSame(10, $relation->refresh()->position);
    }

    public function test_changing_the_target_still_validates_its_existence(): void
    {
        $product = CentralProduct::factory()->create();
        $relation = ContentRelation::factory()->product($product)->create();

        $this->expectException(ValidationException::class);

        $relation->update(['related_id' => 999999]);
    }

    public function test_admin_can_add_and_remove_product_relation(): void
    {
        $site = Site::factory()->create();
        $item = ContentItem::factory()->for($site)->create();
        $product = CentralProduct::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();

        $this->assertContains(RelationsRelationManager::class, ContentItemResource::getRelations());

        Livewire::actingAs($admin)
            ->test(RelationsRelationManager::class, [
                'ownerRecord' => $item,
                'pageClass' => EditContentItem::class,
            ])
            ->callTableAction('create', data: [
                'related_type' => ContentRelationTargetType::Product->value,
                'related_id' => $product->id,
                'relation_type' => 'related',
                'position' => 0,
            ])
            ->assertHasNoTableActionErrors();

        $relation = ContentRelation::query()->sole();

        Livewire::actingAs($admin)
            ->test(RelationsRelationManager::class, [
                'ownerRecord' => $item,
                'pageClass' => EditContentItem::class,
            ])
            ->callTableAction('delete', $relation);

        $this->assertDatabaseCount('content_relations', 0);
    }
}
