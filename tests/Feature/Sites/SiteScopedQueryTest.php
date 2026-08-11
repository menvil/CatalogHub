<?php

declare(strict_types=1);

namespace Tests\Feature\Sites;

use App\Models\ContentItem;
use App\Models\Site;
use App\Queries\Sites\SiteContentQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SiteScopedQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_content_query_excludes_records_from_other_sites(): void
    {
        $firstSite = Site::factory()->create();
        $secondSite = Site::factory()->create();
        $firstContent = ContentItem::factory()->for($firstSite)->create();
        $secondContent = ContentItem::factory()->for($secondSite)->create();

        $query = app(SiteContentQuery::class);

        $this->assertSame([$firstContent->getKey()], $query->forSite($firstSite)->pluck('id')->all());
        $this->assertSame($firstContent->getKey(), $query->find($firstSite, $firstContent->getKey())?->getKey());
        $this->assertNull($query->find($firstSite, $secondContent->getKey()));
    }

    public function test_site_scope_is_explicit_and_not_a_hidden_global_filter(): void
    {
        $firstSite = Site::factory()->create();
        $secondSite = Site::factory()->create();
        $firstContent = ContentItem::factory()->for($firstSite)->create();
        $secondContent = ContentItem::factory()->for($secondSite)->create();

        $this->assertSame(
            [$firstContent->getKey(), $secondContent->getKey()],
            ContentItem::query()->orderBy('id')->pluck('id')->all(),
        );
    }
}
