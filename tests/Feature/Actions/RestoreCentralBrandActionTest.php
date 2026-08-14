<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\RestoreCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RestoreCentralBrandActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_restores_an_archived_brand_to_draft_for_explicit_review(): void
    {
        $brand = CentralBrand::factory()->archived()->create([
            'name' => 'Samsung',
            'country_code' => 'KR',
        ]);

        $result = app(RestoreCentralBrandAction::class)->handle($brand);

        $this->assertSame(CentralBrandStatus::Draft, $result->status);
        $this->assertSame('Samsung', $result->name);
        $this->assertSame('KR', $result->country_code);
    }

    #[DataProvider('nonArchivedStatusProvider')]
    public function test_restore_is_a_safe_no_op_for_non_archived_brands(CentralBrandStatus $status): void
    {
        $brand = CentralBrand::factory()->create(['status' => $status]);

        $result = app(RestoreCentralBrandAction::class)->handle($brand);

        $this->assertSame($status, $result->status);
    }

    /** @return iterable<string, array{CentralBrandStatus}> */
    public static function nonArchivedStatusProvider(): iterable
    {
        yield 'draft' => [CentralBrandStatus::Draft];
        yield 'active' => [CentralBrandStatus::Active];
    }

    public function test_restore_is_idempotent_after_an_archived_brand_returns_to_draft(): void
    {
        $brand = CentralBrand::factory()->archived()->create();

        app(RestoreCentralBrandAction::class)->handle($brand);
        $result = app(RestoreCentralBrandAction::class)->handle($brand->refresh());

        $this->assertSame(CentralBrandStatus::Draft, $result->status);
        $this->assertDatabaseCount('central_brands', 1);
    }
}
