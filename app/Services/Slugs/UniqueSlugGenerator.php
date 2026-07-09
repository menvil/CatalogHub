<?php

namespace App\Services\Slugs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class UniqueSlugGenerator
{
    /**
     * @param class-string<Model> $modelClass
     */
    public function generate(string $source, string $modelClass, string $column = 'slug', ?Model $ignore = null): string
    {
        $baseSlug = Str::slug($source) ?: 'item';
        $slug = $baseSlug;
        $suffix = 2;

        while ($this->exists($modelClass, $column, $slug, $ignore)) {
            $slug = "{$baseSlug}-{$suffix}";
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
}
