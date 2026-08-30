<?php

declare(strict_types=1);

namespace App\Queries\Translations;

use App\Enums\AuditAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use Illuminate\Support\Collection;

final class BrandTranslationActivityQuery
{
    public const LIMIT = 8;

    /** @return Collection<int, AuditLogEntry> */
    public function forBrandAndLocale(CentralBrand $brand, Locale $locale): Collection
    {
        return AuditLogEntry::query()
            ->select([
                'id',
                'actor_id',
                'action',
                'subject_type',
                'subject_id',
                'before_json',
                'after_json',
                'created_at',
            ])
            ->where('subject_type', $brand->getMorphClass())
            ->where('subject_id', (string) $brand->getKey())
            ->whereIn('action', [
                AuditAction::CatalogBrandTranslationSaved->value,
                AuditAction::TranslationApproved->value,
                AuditAction::TranslationMarkedOutdated->value,
            ])
            ->where('after_json->locale', $locale->code)
            ->with('actor:id,name,email')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();
    }
}
