<?php

namespace App\Rules\Content;

use App\Models\ContentTranslation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final readonly class UniqueContentSlug implements ValidationRule
{
    public function __construct(
        private int $siteId,
        private string $locale,
        private ?int $ignoreContentItemId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = ContentTranslation::query()
            ->where('locale', $this->locale)
            ->where('slug', $value)
            ->whereHas('contentItem', fn ($query) => $query->where('site_id', $this->siteId))
            ->when(
                $this->ignoreContentItemId !== null,
                fn ($query) => $query->where('content_item_id', '!=', $this->ignoreContentItemId),
            )
            ->exists();

        if ($exists) {
            $fail('The slug has already been used for this site and locale.');
        }
    }
}
