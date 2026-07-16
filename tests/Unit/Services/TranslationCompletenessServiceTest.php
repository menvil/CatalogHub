<?php

namespace Tests\Unit\Services;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Translations\CategoryTranslation;
use App\Services\Translations\TranslationCompletenessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationCompletenessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculates_translation_completeness_by_locale(): void
    {
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $categories = CentralCategory::factory()->count(10)->create();

        foreach ($categories->take(7) as $category) {
            CategoryTranslation::factory()->create([
                'category_id' => $category->id,
                'locale_id' => $locale->id,
                'locale' => 'de-DE',
                'status' => TranslationStatus::Approved,
            ]);
        }
        CategoryTranslation::factory()->create([
            'category_id' => $categories[7]->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'status' => TranslationStatus::Outdated,
        ]);

        $stats = app(TranslationCompletenessService::class)->forLocale('de-DE');

        $this->assertSame(70.0, $stats['categoryCoverage']);
        $this->assertSame(7, $stats['by_entity']['categories']['approved']);
        $this->assertSame(1, $stats['by_entity']['categories']['outdated']);
        $this->assertSame(2, $stats['by_entity']['categories']['missing']);
    }
}
