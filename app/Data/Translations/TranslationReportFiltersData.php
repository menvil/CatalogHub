<?php

namespace App\Data\Translations;

final readonly class TranslationReportFiltersData
{
    public function __construct(
        public ?string $locale,
        public ?string $entityType,
        public ?string $search = null,
    ) {}
}
