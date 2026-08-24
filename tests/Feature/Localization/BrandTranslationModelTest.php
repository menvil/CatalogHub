<?php

declare(strict_types=1);

namespace Tests\Feature\Localization;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class BrandTranslationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_translation_relations_casts_and_factory_states_follow_common_contract(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $approver = User::factory()->centralAdmin()->create();
        $translation = BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'status' => TranslationStatus::Approved,
            'approved_at' => '2026-08-24 12:00:00',
            'approved_by_user_id' => $approver->id,
        ]);

        $this->assertTrue($translation->brand->is($brand));
        $this->assertTrue($brand->translations->contains($translation));
        $this->assertTrue($translation->localeModel->is($locale));
        $this->assertTrue($translation->approvedBy->is($approver));
        $this->assertSame(TranslationStatus::Approved, $translation->status);
        $this->assertInstanceOf(DateTimeInterface::class, $translation->approved_at);
        $this->assertSame(TranslationStatus::MachineTranslated, BrandTranslation::factory()->machineTranslated()->make()->status);
        $this->assertSame(TranslationStatus::HumanReviewed, BrandTranslation::factory()->humanReviewed()->make()->status);
        $this->assertSame(TranslationStatus::Outdated, BrandTranslation::factory()->outdated()->make()->status);
    }

    public function test_brand_and_locale_pair_is_a_hard_database_invariant(): void
    {
        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
        ]);

        $this->expectException(QueryException::class);

        BrandTranslation::factory()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
        ]);
    }

    public function test_brand_translation_is_deleted_with_its_canonical_brand(): void
    {
        $translation = BrandTranslation::factory()->create();

        $translation->brand->delete();

        $this->assertDatabaseMissing('brand_translations', ['id' => $translation->id]);
    }
}
