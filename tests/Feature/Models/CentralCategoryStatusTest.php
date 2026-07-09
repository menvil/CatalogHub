<?php

namespace Tests\Feature\Models;

use App\Enums\CentralCategoryStatus;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralCategoryStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_central_category_status(): void
    {
        $category = CentralCategory::factory()->create([
            'status' => CentralCategoryStatus::Draft,
        ]);

        $this->assertSame(CentralCategoryStatus::Draft, $category->status);
    }

    public function test_central_category_status_defaults_to_draft(): void
    {
        CentralCategory::query()->create([
            'name' => 'Keyboards',
            'slug' => 'keyboards',
        ]);

        $this->assertSame(CentralCategoryStatus::Draft, CentralCategory::first()->status);
    }

    public function test_central_category_status_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('central_categories'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
    }
}
