<?php

declare(strict_types=1);

namespace Tests\Unit\Navigation;

use App\Enums\UserRole;
use App\Models\User;
use App\Navigation\CentralNavigationRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CentralNavigationRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_has_the_deterministic_unique_central_contract(): void
    {
        $items = app(CentralNavigationRegistry::class)->items();

        $this->assertSame([
            'dashboard',
            'catalog',
            'imports',
            'media',
            'translations',
            'changes',
            'prices',
            'snapshots',
            'users',
            'system',
        ], array_column($items, 'id'));
        $this->assertCount(10, array_unique(array_column($items, 'id')));
        $this->assertCount(10, array_unique(array_column($items, 'route')));
        $this->assertCount(10, array_unique(array_column($items, 'permission')));
    }

    public function test_unavailable_and_unauthorized_items_never_become_dead_links(): void
    {
        $registry = app(CentralNavigationRegistry::class);
        $central = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $translator = User::factory()->create(['role' => UserRole::Translator]);

        $this->assertSame([
            'dashboard',
            'catalog',
            'imports',
            'media',
            'translations',
            'changes',
            'prices',
            'snapshots',
        ], array_column($registry->visibleItemsFor($central), 'id'));
        $this->assertSame([
            'dashboard',
            'catalog',
            'translations',
        ], array_column($registry->visibleItemsFor($translator), 'id'));

        foreach ($registry->visibleItemsFor($central) as $item) {
            $this->assertStringStartsWith('/', $item['url']);
        }
    }
}
