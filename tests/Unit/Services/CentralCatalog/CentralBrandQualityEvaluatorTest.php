<?php

declare(strict_types=1);

namespace Tests\Unit\Services\CentralCatalog;

use App\Enums\CentralBrandQualityIssueCode;
use App\Enums\CentralBrandQualityState;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Services\CentralCatalog\CentralBrandQualityEvaluator;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class CentralBrandQualityEvaluatorTest extends TestCase
{
    public function test_empty_brand_has_six_incomplete_base_checks_and_a_zero_score(): void
    {
        $summary = $this->evaluate(new CentralBrand([
            'id' => 41,
            'name' => 'Incomplete Brand',
            'slug' => 'incomplete-brand',
        ]));

        self::assertSame(CentralBrandQualityState::NeedsAttention, $summary->state);
        self::assertSame(0, $summary->score);
        self::assertSame(0, $summary->completedChecks);
        self::assertSame(6, $summary->totalChecks);
        self::assertSame([
            'brand_country_missing',
            'brand_website_missing',
            'brand_founded_year_missing',
            'brand_support_contact_missing',
            'brand_primary_color_missing',
            'brand_logo_missing',
        ], $summary->issueCodes());
    }

    public function test_complete_brand_uses_equal_applicable_checks_and_support_or_contact_is_one_check(): void
    {
        [$german, $french] = $this->locales();
        $brand = $this->completeBrand();
        $translations = collect([
            $german->id => $this->translation($german, TranslationStatus::Approved),
            $french->id => $this->translation($french, TranslationStatus::HumanReviewed),
        ]);

        $summary = $this->evaluate($brand, collect([$german, $french]), $translations, true, true);

        self::assertSame(CentralBrandQualityState::Complete, $summary->state);
        self::assertSame(100, $summary->score);
        self::assertSame(8, $summary->completedChecks);
        self::assertSame(8, $summary->totalChecks);
        self::assertSame([], $summary->issueCodes());
        self::assertCount(8, $summary->checks);
    }

    public function test_each_missing_profile_component_and_unusable_logo_has_a_stable_issue_code(): void
    {
        $brand = $this->completeBrand();
        $brand->country_id = null;
        $brand->website_url = null;
        $brand->founded_year = null;
        $brand->support_url = null;
        $brand->contact_email = null;
        $brand->primary_color = null;

        $summary = $this->evaluate($brand, hasPrimaryLogo: true, hasUsablePrimaryLogo: false);

        self::assertSame(0, $summary->score);
        self::assertSame([
            CentralBrandQualityIssueCode::CountryMissing,
            CentralBrandQualityIssueCode::WebsiteMissing,
            CentralBrandQualityIssueCode::FoundedYearMissing,
            CentralBrandQualityIssueCode::SupportContactMissing,
            CentralBrandQualityIssueCode::PrimaryColorMissing,
            CentralBrandQualityIssueCode::LogoUnusable,
        ], array_map(static fn ($check) => $check->issueCode, $summary->issues()));
        self::assertSame('central.brands.edit', $summary->issues()[0]->editorRoute);
        self::assertSame('central.brands.media', $summary->issues()[5]->editorRoute);
    }

    public function test_missing_and_outdated_active_locale_translations_are_incomplete_and_deterministic(): void
    {
        [$german, $french, $italian] = $this->locales(3);
        $translations = collect([
            $french->id => $this->translation($french, TranslationStatus::Outdated),
            $italian->id => $this->translation($italian, TranslationStatus::Approved),
        ]);

        $summary = $this->evaluate(
            $this->completeBrand(),
            collect([$german, $french, $italian]),
            $translations,
            true,
            true,
        );

        self::assertSame(78, $summary->score);
        self::assertSame(7, $summary->completedChecks);
        self::assertSame(9, $summary->totalChecks);
        self::assertSame([
            'brand_translation_missing',
            'brand_translation_outdated',
        ], $summary->issueCodes());
        self::assertSame(['de-DE', 'fr-FR'], array_map(static fn ($check) => $check->locale, $summary->issues()));
        self::assertSame('central.brands.translations.edit', $summary->issues()[0]->editorRoute);
        self::assertSame('translations.manage', $summary->issues()[0]->editorPermission);
    }

    public function test_translation_status_missing_is_not_treated_as_present(): void
    {
        [$german] = $this->locales();

        $summary = $this->evaluate(
            $this->completeBrand(),
            collect([$german]),
            collect([$german->id => $this->translation($german, TranslationStatus::Missing)]),
            true,
            true,
        );

        self::assertSame(['brand_translation_missing'], $summary->issueCodes());
        self::assertSame(86, $summary->score);
    }

    public function test_evaluation_does_not_mutate_inputs(): void
    {
        [$german] = $this->locales();
        $brand = $this->completeBrand();
        $translation = $this->translation($german, TranslationStatus::Approved);
        $brandAttributes = $brand->getAttributes();
        $translationAttributes = $translation->getAttributes();

        $this->evaluate($brand, collect([$german]), collect([$german->id => $translation]), true, true);

        self::assertSame($brandAttributes, $brand->getAttributes());
        self::assertSame($translationAttributes, $translation->getAttributes());
        self::assertFalse($brand->isDirty());
        self::assertFalse($translation->isDirty());
    }

    /**
     * @param  Collection<int, Locale>|null  $locales
     * @param  Collection<int, BrandTranslation>|null  $translations
     */
    private function evaluate(
        CentralBrand $brand,
        ?Collection $locales = null,
        ?Collection $translations = null,
        bool $hasPrimaryLogo = false,
        bool $hasUsablePrimaryLogo = false,
    ): mixed {
        return app(CentralBrandQualityEvaluator::class)->evaluate(
            $brand,
            $locales ?? collect(),
            $translations ?? collect(),
            $hasPrimaryLogo,
            $hasUsablePrimaryLogo,
        );
    }

    private function completeBrand(): CentralBrand
    {
        $brand = new CentralBrand([
            'name' => 'Complete Brand',
            'slug' => 'complete-brand',
            'website_url' => 'https://example.test',
            'country_id' => 1,
            'founded_year' => 1999,
            'contact_email' => 'support@example.test',
            'primary_color' => '#123456',
        ]);
        $brand->id = 42;
        $brand->syncOriginal();

        return $brand;
    }

    /** @return list<Locale> */
    private function locales(int $count = 2): array
    {
        $records = [
            [101, 'de-DE', 'German', 'Deutsch'],
            [102, 'fr-FR', 'French', 'Français'],
            [103, 'it-IT', 'Italian', 'Italiano'],
        ];

        return array_map(function (array $record): Locale {
            [$id, $code, $name, $nativeName] = $record;
            $locale = new Locale(['code' => $code, 'name' => $name, 'native_name' => $nativeName, 'is_active' => true]);
            $locale->id = $id;
            $locale->syncOriginal();

            return $locale;
        }, array_slice($records, 0, $count));
    }

    private function translation(Locale $locale, TranslationStatus $status): BrandTranslation
    {
        $translation = new BrandTranslation([
            'brand_id' => 42,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'name' => 'Localized Brand',
            'status' => $status,
        ]);
        $translation->syncOriginal();

        return $translation;
    }
}
