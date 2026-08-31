<?php

declare(strict_types=1);

namespace App\Data\Translations;

use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use Illuminate\Support\Collection;

final readonly class BrandTranslationEditorData
{
    /**
     * @param  Collection<int, Locale>  $locales
     * @param  Collection<int, BrandTranslation>  $translationsByLocale
     * @param  Collection<int, AuditLogEntry>  $activity
     */
    public function __construct(
        public CentralBrand $brand,
        public Collection $locales,
        public Collection $translationsByLocale,
        public ?Locale $selectedLocale,
        public ?BrandTranslation $translation,
        public string $currentSourceHash,
        public bool $sourceHashMatches,
        public Collection $activity,
    ) {}
}
