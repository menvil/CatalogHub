<?php

declare(strict_types=1);

namespace App\Data\Translations;

use App\Enums\TranslationStatus;

final readonly class BrandTranslationInput
{
    public function __construct(
        public string $name,
        public ?string $tagline,
        public ?string $shortDescription,
        public ?string $description,
        public ?string $seoTitle,
        public ?string $seoDescription,
        public TranslationStatus $status,
    ) {}
}
