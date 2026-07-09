<?php

namespace App\Services\Slugs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class UniqueSlugGenerator
{
    /**
     * @param class-string<Model> $modelClass
     */
    public function generate(string $source, string $modelClass, string $column = 'slug', ?Model $ignore = null, int $maxLength = 255): string
    {
        $baseSlug = $this->truncateSlug(Str::slug($source) ?: 'item', $maxLength);
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->exists($modelClass, $column, $slug, $ignore)) {
            $suffixText = "-{$suffix}";
            $slug = $this->truncateSlug($baseSlug, $maxLength - strlen($suffixText)).$suffixText;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param class-string<Model> $modelClass
     */
    private function exists(string $modelClass, string $column, string $slug, ?Model $ignore): bool
    {
        $query = $modelClass::query()->where($column, $slug);

        if ($ignore !== null && $ignore->exists) {
            $query->whereKeyNot($ignore->getKey());
        }

        return $query->exists();
    }

    private function truncateSlug(string $slug, int $maxLength): string
    {
        return Str::limit($slug, max(1, $maxLength), '');
    }
}
