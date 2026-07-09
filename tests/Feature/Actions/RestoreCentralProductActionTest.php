<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\RestoreCentralProductAction;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestoreCentralProductActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_restores_archived_central_product_to_draft(): void
    {
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Archived,
        ]);

        app(RestoreCentralProductAction::class)->handle($product);

        $this->assertSame(CentralProductStatus::Draft, $product->fresh()->status);
    }

    public function test_does_not_change_active_product_when_restore_is_called(): void
    {
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Active,
        ]);

        app(RestoreCentralProductAction::class)->handle($product);

        $this->assertSame(CentralProductStatus::Active, $product->fresh()->status);
    }

    public function test_does_not_change_draft_product_when_restore_is_called(): void
    {
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Draft,
        ]);

        app(RestoreCentralProductAction::class)->handle($product);

        $this->assertSame(CentralProductStatus::Draft, $product->fresh()->status);
    }
}
