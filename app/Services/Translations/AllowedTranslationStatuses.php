<?php

namespace App\Services\Translations;

use App\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Model;

final class AllowedTranslationStatuses
{
    /** @return list<string> */
    public function for(?Model $translation): array
    {
        return collect(TranslationStatus::cases())
            ->reject(fn (TranslationStatus $status): bool => $status === TranslationStatus::Approved
                && $translation?->getAttribute('status') !== TranslationStatus::Approved)
            ->map(fn (TranslationStatus $status): string => $status->value)
            ->values()
            ->all();
    }

    /** @return array<string, string> */
    public function optionsFor(?Model $translation): array
    {
        return array_intersect_key(
            TranslationStatus::options(),
            array_flip($this->for($translation)),
        );
    }
}
