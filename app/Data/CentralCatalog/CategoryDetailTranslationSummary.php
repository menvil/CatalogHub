<?php

declare(strict_types=1);

namespace App\Data\CentralCatalog;

use App\Enums\TranslationStatus;
use App\ValueObjects\Translations\ResolvedTranslation;

final readonly class CategoryDetailTranslationSummary
{
    /** @param array<int, string> $localeOptions */
    public function __construct(
        public array $localeOptions,
        public ?int $localeId,
        public ?string $localeCode,
        public TranslationStatus $status,
        public int $covered,
        public int $missing,
        public int $outdated,
        public ?ResolvedTranslation $description,
    ) {}

    public function total(): int
    {
        return count($this->localeOptions);
    }

    public function percentage(): ?float
    {
        return $this->total() === 0 ? null : round($this->covered / $this->total() * 100, 1);
    }

    public function statusLabel(): string
    {
        return TranslationStatus::options()[$this->status->value];
    }
}
