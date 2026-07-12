<?php

namespace Tests\Feature\Admin;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTranslationEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_save_product_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create(['name' => 'LG Monitor']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $this->actingAs($admin)
            ->post(route('central.products.translations.save', [$product, $locale]), [
                'name' => 'LG Monitor DE',
                'short_description' => 'Gaming-Monitor',
                'description' => 'Beschreibung',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('product_translations', [
            'product_id' => $product->id,
            'locale' => 'de-DE',
            'name' => 'LG Monitor DE',
        ]);
        $this->assertDatabaseHas('central_products', [
            'id' => $product->id,
            'name' => 'LG Monitor',
        ]);
    }
}
