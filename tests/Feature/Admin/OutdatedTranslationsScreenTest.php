<?php

namespace Tests\Feature\Admin;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Translations\ProductTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutdatedTranslationsScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_outdated_product_translation_on_outdated_translations_screen(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $product = CentralProduct::factory()->create(['name' => 'LG Monitor']);

        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'LG Monitor DE',
            'status' => TranslationStatus::Outdated,
        ]);

        $this->actingAs($admin)
            ->get(route('central.translations.outdated'))
            ->assertOk()
            ->assertSee('Outdated Translations')
            ->assertSee('LG Monitor DE');
    }
}
