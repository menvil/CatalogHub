<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\ActivateCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ActivateCentralBrandActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_activates_a_draft_brand_without_changing_other_fields(): void
    {
        $brand = CentralBrand::factory()->draft()->create([
            'name' => 'Samsung',
            'website_url' => 'https://www.samsung.com',
        ]);

        $result = app(ActivateCentralBrandAction::class)->handle($brand);

        $this->assertSame(CentralBrandStatus::Active, $result->status);
        $this->assertSame('Samsung', $result->name);
        $this->assertSame('https://www.samsung.com', $result->website_url);
    }

    public function test_activation_is_idempotent_for_an_active_brand(): void
    {
        $brand = CentralBrand::factory()->active()->create();

        app(ActivateCentralBrandAction::class)->handle($brand);
        $result = app(ActivateCentralBrandAction::class)->handle($brand->refresh());

        $this->assertSame(CentralBrandStatus::Active, $result->status);
        $this->assertDatabaseCount('central_brands', 1);
    }

    public function test_rejects_direct_activation_of_an_archived_brand(): void
    {
        $brand = CentralBrand::factory()->archived()->create(['name' => 'Archived Brand']);

        try {
            app(ActivateCentralBrandAction::class)->handle($brand);
            $this->fail('An archived Brand was activated directly.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Archived brands must be restored before they can be activated.'],
                $exception->errors()['status'] ?? [],
            );
        }

        $brand->refresh();
        $this->assertSame(CentralBrandStatus::Archived, $brand->status);
        $this->assertSame('Archived Brand', $brand->name);
    }
}
