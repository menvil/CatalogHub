<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Resources\ContentItemResource;
use App\Filament\Resources\ContentItemResource\Pages\CreateContentItem;
use App\Filament\Resources\ContentItemResource\Pages\EditContentItem;
use App\Filament\Resources\ContentItemResource\Pages\ListContentItems;
use App\Models\ContentItem;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentAdminResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_exposes_list_create_and_edit_pages(): void
    {
        $this->assertSame(['index', 'create', 'edit'], array_keys(ContentItemResource::getPages()));
        $this->assertTrue(class_exists(ListContentItems::class));
        $this->assertTrue(class_exists(CreateContentItem::class));
        $this->assertTrue(class_exists(EditContentItem::class));
    }

    public function test_site_admin_sees_content_for_their_site_only(): void
    {
        $site = Site::factory()->create();
        $own = ContentItem::factory()->create(['site_id' => $site->id]);
        $other = ContentItem::factory()->create();
        $admin = User::factory()->siteAdmin($site)->create();

        $this->actingAs($admin)->get(ContentItemResource::getUrl())->assertOk();

        Livewire::actingAs($admin)
            ->test(ListContentItems::class)
            ->assertCanSeeTableRecords([$own])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_catalog_editor_cannot_access_content_resource(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)->get(ContentItemResource::getUrl())->assertForbidden();
    }

    public function test_non_super_admin_without_a_site_has_an_empty_content_query(): void
    {
        ContentItem::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SiteAdmin,
            'site_id' => null,
        ]);

        $this->actingAs($user);

        $this->assertSame(0, ContentItemResource::getEloquentQuery()->count());
    }
}
