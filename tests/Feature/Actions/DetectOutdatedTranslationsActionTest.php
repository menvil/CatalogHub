<?php

namespace Tests\Feature\Actions;

use App\Actions\Translations\DetectOutdatedTranslationsAction;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\Translations\ProductTranslation;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetectOutdatedTranslationsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_product_translation_outdated_when_source_hash_changed(): void
    {
        $product = CentralProduct::factory()->create(['name' => 'Old Name']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $hash = app(TranslationSourceHashService::class)->forProduct($product);

        $translation = ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Alter Name',
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        $product->update(['name' => 'New Name']);

        app(DetectOutdatedTranslationsAction::class)->handle($product);

        $this->assertSame(TranslationStatus::Outdated, $translation->fresh()->status);
    }

    public function test_does_not_mark_translation_outdated_when_source_hash_is_unchanged(): void
    {
        $product = CentralProduct::factory()->create(['name' => 'Name']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $hash = app(TranslationSourceHashService::class)->forProduct($product);

        $translation = ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        app(DetectOutdatedTranslationsAction::class)->handle($product);

        $this->assertSame(TranslationStatus::Approved, $translation->fresh()->status);
    }
}
