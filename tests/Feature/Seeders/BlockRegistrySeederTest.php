<?php

namespace Tests\Feature\Seeders;

use App\Enums\BlockStatus;
use App\Models\BlockDefinition;
use Database\Seeders\BlockRegistrySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use RuntimeException;
use Tests\TestCase;

class BlockRegistrySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_updates_are_rolled_back_when_a_later_definition_is_invalid(): void
    {
        $existing = BlockDefinition::factory()->create([
            'code' => 'hero_search',
            'name' => 'Original Hero',
            'status' => BlockStatus::Active,
        ]);
        Config::set('cataloghub_blocks', [
            'hero_search' => ['name' => 'Updated Hero'],
            42 => 'invalid definition',
        ]);

        try {
            $this->seed(BlockRegistrySeeder::class);
            $this->fail('An invalid registry definition did not abort the seed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('string code and array definition', $exception->getMessage());
        }

        $this->assertSame('Original Hero', $existing->fresh()->name);
    }
}
