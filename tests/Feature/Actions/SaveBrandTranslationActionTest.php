<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Translations\SaveBrandTranslationAction;
use App\Data\Translations\BrandTranslationInput;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Queries\Translations\BrandTranslationEditorQuery;
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class SaveBrandTranslationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_and_updates_one_server_bound_translation_with_current_source_hash(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung Electronics', 'slug' => 'samsung']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $action = app(SaveBrandTranslationAction::class);

        $created = $action->handle($brand, $locale, $this->input(
            name: 'Samsung',
            tagline: 'Technologie für jeden',
        ));

        $this->assertSame($brand->id, $created->brand_id);
        $this->assertSame($locale->id, $created->locale_id);
        $this->assertSame('de-DE', $created->locale);
        $this->assertSame(TranslationStatus::HumanReviewed, $created->status);
        $this->assertSame(app(TranslationSourceHashService::class)->forBrand($brand), $created->source_hash);

        $brand->update(['name' => 'Samsung Electronics Co.']);
        $updated = $action->handle($brand, $locale, $this->input(
            name: 'Samsung Deutschland',
            tagline: null,
            seoDescription: null,
            status: TranslationStatus::MachineTranslated,
        ));

        $this->assertTrue($created->is($updated));
        $this->assertSame('Samsung Deutschland', $updated->name);
        $this->assertNull($updated->tagline);
        $this->assertNull($updated->seo_description);
        $this->assertSame(TranslationStatus::MachineTranslated, $updated->status);
        $this->assertSame(app(TranslationSourceHashService::class)->forBrand($brand), $updated->source_hash);
        $this->assertSame(1, BrandTranslation::query()->count());
    }

    public function test_save_invalidates_the_shared_translation_dashboard_cache(): void
    {
        Cache::put('translations.dashboard.stats', ['stale' => true], 60);

        app(SaveBrandTranslationAction::class)->handle(
            CentralBrand::factory()->create(),
            Locale::factory()->create(),
            $this->input(),
        );

        $this->assertFalse(Cache::has('translations.dashboard.stats'));
    }

    public function test_locale_code_rename_updates_the_existing_locale_identity(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $action = app(SaveBrandTranslationAction::class);
        $created = $action->handle($brand, $locale, $this->input(name: 'Samsung Deutschland'));

        $locale->update(['code' => 'de-AT']);
        $locale->refresh();

        $editor = app(BrandTranslationEditorQuery::class)->forBrand($brand, $locale);
        $this->assertTrue($created->is($editor->translation));

        $updated = $action->handle($brand, $locale, $this->input(name: 'Samsung Österreich'));

        $this->assertTrue($created->is($updated));
        $this->assertSame($locale->id, $updated->locale_id);
        $this->assertSame('de-AT', $updated->locale);
        $this->assertSame('Samsung Österreich', $updated->name);
        $this->assertSame(1, BrandTranslation::query()->count());
    }

    private function input(
        string $name = 'Samsung',
        ?string $tagline = 'Technology for everyone',
        ?string $seoDescription = 'Samsung products',
        TranslationStatus $status = TranslationStatus::HumanReviewed,
    ): BrandTranslationInput {
        return new BrandTranslationInput(
            name: $name,
            tagline: $tagline,
            shortDescription: 'Localized summary',
            description: 'Localized description',
            seoTitle: 'Samsung localized',
            seoDescription: $seoDescription,
            status: $status,
        );
    }
}
