<?php

namespace Tests\Feature\Themes;

use App\Domains\Themes\Services\BlockRegistry;
use App\Enums\BlockStatus;
use App\Models\BlockDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_lists_active_blocks_and_excludes_archived_entries(): void
    {
        $active = BlockDefinition::factory()->create([
            'code' => 'hero_search',
            'name' => 'Hero Search',
            'status' => BlockStatus::Active,
        ]);
        BlockDefinition::factory()->create(['status' => BlockStatus::Draft]);
        BlockDefinition::factory()->create(['status' => BlockStatus::Archived]);
        $registry = app(BlockRegistry::class);

        $this->assertSame([$active->id], $registry->activeBlocks()->pluck('id')->all());
        $this->assertTrue($registry->findByCode('hero_search')?->is($active));
        $this->assertNull($registry->findByCode('missing_block'));
    }

    public function test_registry_filters_blocks_by_page_type(): void
    {
        $home = BlockDefinition::factory()->create([
            'code' => 'hero_search',
            'name' => 'Hero Search',
            'status' => BlockStatus::Active,
            'supported_page_types_json' => ['home'],
        ]);
        $shared = BlockDefinition::factory()->create([
            'code' => 'lead_form',
            'name' => 'Lead Form',
            'status' => BlockStatus::Active,
            'supported_page_types_json' => ['home', 'product'],
        ]);
        BlockDefinition::factory()->create([
            'status' => BlockStatus::Archived,
            'supported_page_types_json' => ['home'],
        ]);
        $registry = app(BlockRegistry::class);

        $this->assertEqualsCanonicalizing([$home->id, $shared->id], $registry->forPageType('home')->pluck('id')->all());
        $this->assertSame([$shared->id], $registry->forPageType('product')->pluck('id')->all());
        $this->assertTrue($registry->blockSupportsPage('hero_search', 'home'));
        $this->assertFalse($registry->blockSupportsPage('hero_search', 'product'));
        $this->assertFalse($registry->blockSupportsPage('missing_block', 'home'));
    }
}
