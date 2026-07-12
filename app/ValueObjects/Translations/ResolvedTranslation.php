<?php

namespace App\ValueObjects\Translations;

use App\Enums\TranslationStatus;
use Illuminate\Database\Eloquent\Model;

final readonly class ResolvedTranslation
{
    public function __construct(
        public mixed $value,
        public string $locale,
        public TranslationStatus $status,
        public string $source,
        public ?Model $translationModel = null,
    ) {}
}
