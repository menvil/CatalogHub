<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\ArchiveCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ArchiveCentralBrandActionTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('archivableStatusProvider')]
    public function test_archives_draft_and_active_brands(CentralBrandStatus $status): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'status' => $status,
            'country_code' => 'KR',
        ]);

        $result = app(ArchiveCentralBrandAction::class)->handle(User::factory()->create(), $brand);

        $this->assertSame(CentralBrandStatus::Archived, $result->status);
        $this->assertSame('Samsung', $result->name);
        $this->assertSame('KR', $result->country_code);
    }

    /** @return iterable<string, array{CentralBrandStatus}> */
    public static function archivableStatusProvider(): iterable
    {
        yield 'draft' => [CentralBrandStatus::Draft];
        yield 'active' => [CentralBrandStatus::Active];
    }

    public function test_archiving_is_idempotent_for_an_archived_brand(): void
    {
        $brand = CentralBrand::factory()->archived()->create();

        app(ArchiveCentralBrandAction::class)->handle(User::factory()->create(), $brand);
        $result = app(ArchiveCentralBrandAction::class)->handle(User::factory()->create(), $brand->refresh());

        $this->assertSame(CentralBrandStatus::Archived, $result->status);
        $this->assertDatabaseCount('central_brands', 1);
    }
}
