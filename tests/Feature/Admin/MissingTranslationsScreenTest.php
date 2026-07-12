<?php

namespace Tests\Feature\Admin;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MissingTranslationsScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_missing_product_translation_on_missing_translations_screen(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        Locale::factory()->create(['code' => 'de-DE', 'is_active' => true]);
        CentralProduct::factory()->create(['name' => 'LG Monitor']);

        $this->actingAs($admin)
            ->get(route('central.translations.missing', ['entity_type' => 'product']))
            ->assertOk()
            ->assertSee('Missing Translations')
            ->assertSee('LG Monitor')
            ->assertSee('de-DE');
    }
}
