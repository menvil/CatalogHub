<?php

namespace App\Actions\Content;

use App\Models\ContentItem;
use App\Models\ContentTranslation;
use InvalidArgumentException;

final class UpsertContentTranslationAction
{
    /** @param array<string, mixed> $attributes */
    public function handle(ContentItem $item, array $attributes): ContentTranslation
    {
        $locale = $attributes['locale'] ?? null;

        if (! is_string($locale) || $locale === '') {
            throw new InvalidArgumentException('A content translation locale is required.');
        }

        return $item->translations()->updateOrCreate(
            ['locale' => $locale],
            $attributes,
        );
    }
}
