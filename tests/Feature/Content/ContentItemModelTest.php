<?php

namespace Tests\Feature\Content;

use App\Models\ContentItem;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentItemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_item_can_be_created_with_site_and_audit_relationships(): void
    {
        $site = Site::factory()->create();
        $creator = User::factory()->create();
        $item = ContentItem::factory()->create([
            'site_id' => $site->id,
            'created_by_user_id' => $creator->id,
            'updated_by_user_id' => $creator->id,
            'metadata' => ['featured' => true],
        ]);

        $this->assertTrue($item->site->is($site));
        $this->assertTrue($item->creator->is($creator));
        $this->assertTrue($item->updater->is($creator));
        $this->assertSame(['featured' => true], $item->metadata);
    }

    public function test_content_item_scopes_filter_site_status_and_type(): void
    {
        $site = Site::factory()->create();
        $published = ContentItem::factory()->create([
            'site_id' => $site->id,
            'type' => 'article',
            'status' => 'published',
        ]);
        ContentItem::factory()->create(['site_id' => $site->id, 'status' => 'draft']);
        ContentItem::factory()->create(['type' => 'article', 'status' => 'published']);

        $items = ContentItem::query()->forSite($site)->published()->ofType('article')->get();

        $this->assertCount(1, $items);
        $this->assertTrue($items->first()->is($published));
    }
}
