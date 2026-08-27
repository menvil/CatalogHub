<?php

declare(strict_types=1);

namespace App\Services\CentralCatalog;

use App\Data\CentralCatalog\CentralBrandQualityCheck;
use App\Data\CentralCatalog\CentralBrandQualitySummary;
use App\Enums\CentralBrandQualityIssueCode;
use App\Enums\CentralBrandQualityState;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use Illuminate\Support\Collection;

final class CentralBrandQualityEvaluator
{
    /**
     * @param  Collection<int, Locale>  $activeLocales
     * @param  Collection<int, BrandTranslation>  $translationsByLocale
     */
    public function evaluate(
        CentralBrand $brand,
        Collection $activeLocales,
        Collection $translationsByLocale,
        bool $hasPrimaryLogo,
        bool $hasUsablePrimaryLogo,
    ): CentralBrandQualitySummary {
        $checks = [
            $this->profileCheck(
                key: 'canonical_country',
                completed: $brand->country_id !== null,
                completeLabel: 'Country is present',
                issueLabel: 'Country is missing',
                issueDescription: 'Add a country to the canonical Brand profile.',
                issueCode: CentralBrandQualityIssueCode::CountryMissing,
                brand: $brand,
            ),
            $this->profileCheck(
                key: 'canonical_website',
                completed: $this->hasText($brand->website_url),
                completeLabel: 'Website is present',
                issueLabel: 'Website is missing',
                issueDescription: 'Add the canonical Brand website.',
                issueCode: CentralBrandQualityIssueCode::WebsiteMissing,
                brand: $brand,
            ),
            $this->profileCheck(
                key: 'canonical_founded_year',
                completed: $brand->founded_year !== null,
                completeLabel: 'Founded year is present',
                issueLabel: 'Founded year is missing',
                issueDescription: 'Add the year the Brand was founded.',
                issueCode: CentralBrandQualityIssueCode::FoundedYearMissing,
                brand: $brand,
            ),
            $this->profileCheck(
                key: 'canonical_support_contact',
                completed: $this->hasText($brand->support_url) || $this->hasText($brand->contact_email),
                completeLabel: 'Support or contact information is present',
                issueLabel: 'Support or contact information is missing',
                issueDescription: 'Add either a support URL or a contact email to the canonical Brand profile.',
                issueCode: CentralBrandQualityIssueCode::SupportContactMissing,
                brand: $brand,
            ),
            $this->profileCheck(
                key: 'canonical_primary_color',
                completed: $this->hasText($brand->primary_color),
                completeLabel: 'Primary color is present',
                issueLabel: 'Primary color is missing',
                issueDescription: 'Add the Brand primary color.',
                issueCode: CentralBrandQualityIssueCode::PrimaryColorMissing,
                brand: $brand,
            ),
            $this->logoCheck($brand, $hasPrimaryLogo, $hasUsablePrimaryLogo),
        ];

        foreach ($activeLocales as $locale) {
            $translation = $translationsByLocale->get((int) $locale->getKey());
            $checks[] = $this->translationCheck(
                $brand,
                $locale,
                $translation instanceof BrandTranslation ? $translation : null,
            );
        }

        $completed = count(array_filter(
            $checks,
            static fn (CentralBrandQualityCheck $check): bool => $check->completed,
        ));
        $total = count($checks);
        $score = (int) round(($completed / $total) * 100, 0, PHP_ROUND_HALF_UP);

        return new CentralBrandQualitySummary(
            state: $completed === $total
                ? CentralBrandQualityState::Complete
                : CentralBrandQualityState::NeedsAttention,
            score: $score,
            completedChecks: $completed,
            totalChecks: $total,
            checks: $checks,
        );
    }

    private function profileCheck(
        string $key,
        bool $completed,
        string $completeLabel,
        string $issueLabel,
        string $issueDescription,
        CentralBrandQualityIssueCode $issueCode,
        CentralBrand $brand,
    ): CentralBrandQualityCheck {
        return new CentralBrandQualityCheck(
            key: $key,
            label: $completed ? $completeLabel : $issueLabel,
            description: $completed ? 'This canonical profile check is complete.' : $issueDescription,
            completed: $completed,
            issueCode: $completed ? null : $issueCode,
            editorRoute: $completed ? null : 'central.brands.edit',
            editorRouteParameters: $completed ? [] : [$brand],
            editorPermission: $completed ? null : 'catalog.brands.manage',
            editorLabel: $completed ? null : 'Edit profile',
        );
    }

    private function logoCheck(CentralBrand $brand, bool $hasPrimaryLogo, bool $hasUsablePrimaryLogo): CentralBrandQualityCheck
    {
        if (! $hasPrimaryLogo) {
            return new CentralBrandQualityCheck(
                key: 'global_primary_logo',
                label: 'Primary Brand logo is missing',
                description: 'Assign a global primary Brand logo.',
                completed: false,
                issueCode: CentralBrandQualityIssueCode::LogoMissing,
                editorRoute: 'central.brands.media',
                editorRouteParameters: [$brand],
                editorPermission: 'catalog.brands.manage',
                editorLabel: 'Manage logo',
            );
        }

        if (! $hasUsablePrimaryLogo) {
            return new CentralBrandQualityCheck(
                key: 'global_primary_logo',
                label: 'Primary Brand logo is unavailable',
                description: 'The assigned Brand logo has no usable ready variant or master file.',
                completed: false,
                issueCode: CentralBrandQualityIssueCode::LogoUnusable,
                editorRoute: 'central.brands.media',
                editorRouteParameters: [$brand],
                editorPermission: 'catalog.brands.manage',
                editorLabel: 'Manage logo',
            );
        }

        return new CentralBrandQualityCheck(
            key: 'global_primary_logo',
            label: 'Primary Brand logo is usable',
            description: 'A usable global primary Brand logo is assigned.',
            completed: true,
        );
    }

    private function translationCheck(CentralBrand $brand, Locale $locale, ?BrandTranslation $translation): CentralBrandQualityCheck
    {
        $localeCode = (string) $locale->code;
        $localeName = $this->hasText($locale->name) ? (string) $locale->name : $localeCode;
        $localeLabel = "{$localeName} ({$localeCode})";
        $status = $translation?->getAttribute('status');

        if (! $status instanceof TranslationStatus) {
            $status = TranslationStatus::Missing;
        }

        return match ($status) {
            TranslationStatus::Missing => new CentralBrandQualityCheck(
                key: 'translation:'.$localeCode,
                label: $localeLabel.' translation is missing',
                description: 'Add a translation for this active locale.',
                completed: false,
                issueCode: CentralBrandQualityIssueCode::TranslationMissing,
                editorRoute: 'central.brands.translations.edit',
                editorRouteParameters: [$brand, $localeCode],
                editorPermission: 'translations.manage',
                editorLabel: 'Edit translation',
                locale: $localeCode,
            ),
            TranslationStatus::Outdated => new CentralBrandQualityCheck(
                key: 'translation:'.$localeCode,
                label: $localeLabel.' translation is outdated',
                description: 'Review this translation because its canonical source changed.',
                completed: false,
                issueCode: CentralBrandQualityIssueCode::TranslationOutdated,
                editorRoute: 'central.brands.translations.edit',
                editorRouteParameters: [$brand, $localeCode],
                editorPermission: 'translations.manage',
                editorLabel: 'Review translation',
                locale: $localeCode,
            ),
            TranslationStatus::MachineTranslated,
            TranslationStatus::HumanReviewed,
            TranslationStatus::Approved => new CentralBrandQualityCheck(
                key: 'translation:'.$localeCode,
                label: $localeLabel.' translation is present',
                description: 'A current translation exists for this active locale.',
                completed: true,
                locale: $localeCode,
            ),
        };
    }

    private function hasText(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }
}
