<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CategoryDetailTranslationSummary;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Translations\CategoryTranslation;
use App\Services\Translations\TranslationResolver;
use Illuminate\Validation\ValidationException;

final readonly class CategoryDetailTranslationQuery
{
    public function __construct(private TranslationResolver $resolver) {}

    public function forCategory(CentralCategory $category, ?int $localeId): CategoryDetailTranslationSummary
    {
        $locales = Locale::query()->active()->orderByDesc('is_default')->orderBy('position')->orderBy('code')->orderBy('id')
            ->get(['id', 'code', 'is_default', 'position']);
        if ($localeId !== null && ! $locales->contains('id', $localeId)) {
            throw ValidationException::withMessages(['locale' => __('validation.exists', ['attribute' => 'locale'])]);
        }
        $selected = $localeId === null ? $locales->first() : $locales->firstWhere('id', $localeId);
        $rows = CategoryTranslation::query()->where('category_id', $category->id)->whereIn('locale_id', $locales->modelKeys())
            ->get(['locale_id', 'status'])->keyBy('locale_id');
        $missing = $locales->count() - $rows->where('status', '!=', TranslationStatus::Missing)->count();
        $outdated = $rows->where('status', TranslationStatus::Outdated)->count();

        return new CategoryDetailTranslationSummary(
            localeOptions: $locales->pluck('code', 'id')->all(),
            localeId: $selected?->id,
            localeCode: $selected?->code,
            status: $selected === null ? TranslationStatus::Missing : ($rows->get($selected->id)->status ?? TranslationStatus::Missing),
            covered: $locales->count() - $missing - $outdated,
            missing: $missing,
            outdated: $outdated,
            description: $selected === null ? null : $this->resolver->resolve($category, 'description', $selected->code),
        );
    }
}
