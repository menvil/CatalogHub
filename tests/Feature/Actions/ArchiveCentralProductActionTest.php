<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\ArchiveCentralProductAction;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchiveCentralProductActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_archives_active_central_product(): void
    {
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Active,
        ]);

        app(ArchiveCentralProductAction::class)->handle($product);

        $this->assertSame(CentralProductStatus::Archived, $product->fresh()->status);
    }

    public function test_archives_draft_central_product(): void
    {
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Draft,
        ]);

        app(ArchiveCentralProductAction::class)->handle($product);

        $this->assertSame(CentralProductStatus::Archived, $product->fresh()->status);
    }

    public function test_keeps_archived_product_archived_when_archiving_again(): void
    {
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Archived,
        ]);

        app(ArchiveCentralProductAction::class)->handle($product);

        $this->assertSame(CentralProductStatus::Archived, $product->fresh()->status);
    }
}
