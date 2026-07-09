<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_central_category(): void
    {
        $category = CentralCategory::factory()->create([
            'name' => 'Monitors',
            'slug' => 'monitors',
        ]);

        $this->assertTrue($category->exists);
        $this->assertSame('Monitors', $category->name);
        $this->assertSame('monitors', $category->slug);
    }

    public function test_central_category_defaults_to_zero_position(): void
    {
        $category = CentralCategory::factory()->create();

        $this->assertSame(0, $category->position);
    }

    public function test_central_category_position_casts_to_integer(): void
    {
        $category = CentralCategory::factory()->create([
            'position' => '7',
        ]);

        $this->assertSame(7, $category->position);
    }
}
