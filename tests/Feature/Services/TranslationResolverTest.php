<?php

namespace Tests\Feature\Services;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Translations\ProductTranslation;
use App\Services\Translations\TranslationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_exact_product_translation_field(): void
    {
        $product = CentralProduct::factory()->create(['name' => 'LG UltraGear 27GP850-B']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'LG UltraGear 27GP850-B Monitor',
            'status' => TranslationStatus::Approved,
        ]);

        $value = app(TranslationResolver::class)->resolve($product, 'name', 'de-DE');

        $this->assertSame('LG UltraGear 27GP850-B Monitor', $value->value);
        $this->assertSame(TranslationStatus::Approved, $value->status);
        $this->assertSame('exact', $value->source);
    }

    public function test_falls_back_to_default_locale_translation_when_requested_locale_is_missing(): void
    {
        $product = CentralProduct::factory()->create(['name' => 'Source Name']);
        $locale = Locale::factory()->create(['code' => 'en-US', 'is_default' => true]);

        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'en-US',
            'name' => 'English Name',
            'status' => TranslationStatus::Approved,
        ]);

        $resolved = app(TranslationResolver::class)->resolve($product, 'name', 'de-DE');

        $this->assertSame('English Name', $resolved->value);
        $this->assertSame('fallback_locale', $resolved->source);
    }

    public function test_falls_back_to_source_field_when_no_translation_exists(): void
    {
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);

        $resolved = app(TranslationResolver::class)->resolve($category, 'name', 'de-DE');

        $this->assertSame('Monitors', $resolved->value);
        $this->assertSame('source', $resolved->source);
        $this->assertSame(TranslationStatus::Missing, $resolved->status);
    }
}
