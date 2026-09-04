<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\AuditAction;
use App\Enums\TranslationStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Services\Translations\TranslationSourceHashService;
use Carbon\CarbonImmutable;
use RuntimeException;

final class BrandTranslationFixture
{
    public const VERSION = 'brand-translations-v2';

    public const WORKSPACE_BRAND_ID = BrandDetailFixture::DRAFT_BRAND_ID;

    public static function create(): void
    {
        $brand = CentralBrand::query()->find(BrandDetailFixture::ACTIVE_BRAND_ID);

        if (! $brand instanceof CentralBrand || $brand->slug !== 'samsung') {
            throw new RuntimeException('BrandTranslationFixture requires the deterministic Samsung Brand detail fixture.');
        }

        $workspaceBrand = CentralBrand::query()->find(self::WORKSPACE_BRAND_ID);

        if (! $workspaceBrand instanceof CentralBrand || $workspaceBrand->slug !== 'zotac') {
            throw new RuntimeException('BrandTranslationFixture requires the deterministic Zotac workspace Brand.');
        }

        Locale::query()->update(['is_default' => false]);

        foreach ([
            ['code' => 'en-US', 'language_code' => 'en', 'region_code' => 'US', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'position' => 0],
            ['code' => 'de-DE', 'language_code' => 'de', 'region_code' => 'DE', 'name' => 'German', 'native_name' => 'Deutsch', 'is_default' => false, 'position' => 1],
            ['code' => 'fr-FR', 'language_code' => 'fr', 'region_code' => 'FR', 'name' => 'French', 'native_name' => 'Français', 'is_default' => false, 'position' => 2],
            ['code' => 'ar-SA', 'language_code' => 'ar', 'region_code' => 'SA', 'name' => 'Arabic', 'native_name' => 'العربية', 'is_default' => false, 'position' => 3, 'direction' => 'rtl', 'is_active' => false],
        ] as $attributes) {
            $locale = Locale::query()->firstOrNew(['code' => $attributes['code']]);
            $locale->forceFill([
                ...$attributes,
                'direction' => $attributes['direction'] ?? 'ltr',
                'is_active' => $attributes['is_active'] ?? true,
            ])->saveOrFail();
        }

        BrandTranslation::query()
            ->whereIn('brand_id', [BrandDetailFixture::NEEDS_ATTENTION_BRAND_ID, BrandDetailFixture::COMPLETE_BRAND_ID, self::WORKSPACE_BRAND_ID])
            ->delete();

        $sourceHash = app(TranslationSourceHashService::class)->forBrand($workspaceBrand);
        $actor = User::query()->where('email', 'translator@demo.cataloghub.test')->firstOrFail();
        $locales = Locale::query()->get()->keyBy('code');

        $approved = self::translation(
            id: 1501501,
            brand: $workspaceBrand,
            locale: $locales->get('en-US'),
            sourceHash: $sourceHash,
            status: TranslationStatus::Approved,
            actor: $actor,
            name: 'Zotac',
            tagline: 'Innovation beyond the expected.',
            shortDescription: 'Deterministic approved target copy for the CA-015 workspace.',
        );
        $outdated = self::translation(
            id: 1501502,
            brand: $workspaceBrand,
            locale: $locales->get('fr-FR'),
            sourceHash: $sourceHash,
            status: TranslationStatus::Outdated,
            actor: null,
            name: 'Zotac',
            tagline: 'L’innovation au-delà des attentes.',
            shortDescription: 'Copie cible déterministe marquée comme obsolète.',
        );
        self::translation(
            id: 1501503,
            brand: $workspaceBrand,
            locale: $locales->get('en-DE'),
            sourceHash: $sourceHash,
            status: TranslationStatus::HumanReviewed,
            actor: null,
            name: 'Zotac Germany',
            tagline: 'Deterministic reviewed target copy.',
            shortDescription: 'Human-reviewed fixture for the fourth active Locale.',
        );
        self::translation(
            id: 1501504,
            brand: $workspaceBrand,
            locale: $locales->get('ar-SA'),
            sourceHash: $sourceHash,
            status: TranslationStatus::HumanReviewed,
            actor: null,
            name: 'زوتاك',
            tagline: 'ابتكار يتجاوز المتوقع.',
            shortDescription: 'نسخة عربية حتمية لاختبار اتجاه حقول الهدف.',
        );

        self::activity($workspaceBrand, $actor, AuditAction::CatalogBrandTranslationSaved, $approved->id, 'en-US', TranslationStatus::HumanReviewed, ['name', 'tagline', 'short_description'], '2026-08-24T09:00:00Z');
        self::activity($workspaceBrand, $actor, AuditAction::TranslationApproved, $approved->id, 'en-US', TranslationStatus::Approved, ['status', 'approval'], '2026-08-25T10:30:00Z');
        self::activity($workspaceBrand, $actor, AuditAction::CatalogBrandTranslationSaved, $outdated->id, 'fr-FR', TranslationStatus::HumanReviewed, ['name', 'tagline', 'short_description'], '2026-08-23T08:00:00Z');
        self::activity($workspaceBrand, $actor, AuditAction::TranslationMarkedOutdated, $outdated->id, 'fr-FR', TranslationStatus::Outdated, ['status'], '2026-08-26T11:45:00Z');

        foreach (Locale::query()->active()->orderBy('position')->orderBy('code')->get() as $locale) {
            BrandTranslation::factory()->create([
                'brand_id' => BrandDetailFixture::COMPLETE_BRAND_ID,
                'locale_id' => $locale->getKey(),
                'locale' => $locale->code,
                'name' => 'Sony '.$locale->code,
                'tagline' => 'Complete deterministic Brand translation.',
                'short_description' => 'Complete Brand quality fixture for '.$locale->code.'.',
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
                'status' => TranslationStatus::HumanReviewed,
            ]);
        }

        CentralBrand::query()->whereKey(BrandDetailFixture::NEEDS_ATTENTION_BRAND_ID)->update([
            'updated_at' => CarbonImmutable::parse('2026-07-26T09:00:00Z'),
        ]);
        CentralBrand::query()->whereKey(BrandDetailFixture::COMPLETE_BRAND_ID)->update([
            'updated_at' => CarbonImmutable::parse('2026-07-27T09:00:00Z'),
        ]);
    }

    private static function translation(
        int $id,
        CentralBrand $brand,
        mixed $locale,
        string $sourceHash,
        TranslationStatus $status,
        ?User $actor,
        string $name,
        string $tagline,
        string $shortDescription,
    ): BrandTranslation {
        if (! $locale instanceof Locale) {
            throw new RuntimeException('BrandTranslationFixture locale is missing.');
        }

        $timestamp = CarbonImmutable::parse('2026-08-22T08:00:00Z');
        $translation = new BrandTranslation;
        $translation->forceFill([
            'id' => $id,
            'brand_id' => $brand->getKey(),
            'locale_id' => $locale->getKey(),
            'locale' => $locale->code,
            'name' => $name,
            'tagline' => $tagline,
            'short_description' => $shortDescription,
            'description' => 'Long deterministic localized Brand description for visual layout and wrapping checks.',
            'seo_title' => $name.' | CatalogHub',
            'seo_description' => 'Deterministic localized SEO description for CA-015 visual and browser acceptance.',
            'status' => $status,
            'source_hash' => $sourceHash,
            'approved_at' => $status === TranslationStatus::Approved ? CarbonImmutable::parse('2026-08-25T10:30:00Z') : null,
            'approved_by_user_id' => $status === TranslationStatus::Approved ? $actor?->getKey() : null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();

        return $translation;
    }

    /** @param list<string> $changedFields */
    private static function activity(
        CentralBrand $brand,
        User $actor,
        AuditAction $action,
        int $translationId,
        string $locale,
        TranslationStatus $status,
        array $changedFields,
        string $createdAt,
    ): void {
        $entry = new AuditLogEntry;
        $entry->forceFill([
            'actor_id' => $actor->getKey(),
            'context' => 'central',
            'site_id' => null,
            'action' => $action->value,
            'subject_type' => $brand->getMorphClass(),
            'subject_id' => (string) $brand->getKey(),
            'before_json' => null,
            'after_json' => [
                'translation_id' => $translationId,
                'locale' => $locale,
                'status' => $status->value,
                'changed_fields' => $changedFields,
            ],
            'request_id' => 'ca015-fixture-'.$translationId.'-'.$action->value,
            'created_at' => CarbonImmutable::parse($createdAt),
        ])->saveOrFail();
    }

    private function __construct() {}
}
