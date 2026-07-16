<?php

namespace Tests\Feature\CentralAdmin;

use App\Filament\Resources\CentralProductResource;
use App\Filament\Resources\CentralProductResource\Pages\ViewProductVersions;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProductVersion;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVersionHistoryScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_render_product_version_history(): void
    {
        $product = CentralProduct::factory()->create();
        $version = ProductVersion::factory()->for($product, 'centralProduct')->create([
            'version' => 2,
            'reason' => 'Corrected title.',
            'diff_json' => ['name' => ['old' => 'Old title', 'new' => 'New title']],
        ]);
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(CentralProductResource::getUrl('versions', ['record' => $product]))
            ->assertOk()
            ->assertSee('Version History')
            ->assertSee('Corrected title.');

        Livewire::actingAs($admin)
            ->test(ViewProductVersions::class, ['record' => $product->id])
            ->assertCanSeeTableRecords([$version])
            ->assertTableActionExists('viewDiff', record: $version);

        $preview = view(
            'filament.resources.central-product-resource.pages.version-history-entry',
            ['version' => $version],
        )->render();

        $this->assertStringContainsString('Old title', $preview);
        $this->assertStringContainsString('New title', $preview);
    }

    public function test_versions_are_sorted_descending_and_empty_state_is_available(): void
    {
        $product = CentralProduct::factory()->create();
        $older = ProductVersion::factory()->for($product, 'centralProduct')->create(['version' => 2]);
        $newer = ProductVersion::factory()->for($product, 'centralProduct')->create(['version' => 3]);
        $admin = User::factory()->centralAdmin()->create();

        Livewire::actingAs($admin)
            ->test(ViewProductVersions::class, ['record' => $product->id])
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true);

        Livewire::actingAs($admin)
            ->test(ViewProductVersions::class, ['record' => CentralProduct::factory()->create()->id])
            ->assertSee('No product versions yet');
    }

    public function test_site_admin_cannot_open_central_version_history(): void
    {
        $product = CentralProduct::factory()->create();
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(CentralProductResource::getUrl('versions', ['record' => $product]))
            ->assertForbidden();
    }
}
