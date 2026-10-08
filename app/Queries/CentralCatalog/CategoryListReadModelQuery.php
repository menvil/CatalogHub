<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Contracts\Persistence\StablePaginationBoundary;
use App\Data\CentralCatalog\CategoryListFiltersData;
use App\Data\CentralCatalog\CategoryListReadModelData;
use App\Data\CentralCatalog\CategoryListRow;
use App\Data\CentralCatalog\CategoryListSummary;
use App\Enums\CategorySchemaStatus;
use App\Enums\CentralCategoryStatus;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\Translations\CategoryTranslation;
use App\Support\Database\LiteralLikePattern;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

final class CategoryListReadModelQuery implements RawSqlPersistenceBoundary, StablePaginationBoundary
{
    public function paginate(CategoryListFiltersData $filters, ?int $page = null): CategoryListReadModelData
    {
        // One minimal tree snapshot, independent of page size or hierarchy depth.
        $tree = CentralCategory::query()->orderBy('id')->get(['id', 'parent_id', 'name', 'status', 'schema_status'])->keyBy('id');
        $depths = $this->depths($tree);
        $locales = Locale::query()->active()->orderBy('position')->orderBy('code')->pluck('code', 'id')->all();
        $sites = Site::query()->administrable()->orderBy('name')->orderBy('id')->pluck('name', 'id')->all();
        if ($filters->localeId !== null && ! array_key_exists($filters->localeId, $locales)) {
            throw ValidationException::withMessages(['locale' => __('validation.exists', ['attribute' => 'locale'])]);
        }
        if ($filters->siteId !== null && ! array_key_exists($filters->siteId, $sites)) {
            throw ValidationException::withMessages(['site' => __('validation.exists', ['attribute' => 'site'])]);
        }
        $localeIds = array_keys($locales);
        $summary = new CategoryListSummary(
            total: $tree->count(),
            active: $tree->where('status', CentralCategoryStatus::Active)->count(),
            withSchema: CentralCategory::query()->has('attributeAssignments')->count(),
            missingTranslations: $this->translationScope(CentralCategory::query(), $localeIds, 'missing')->count(),
            needsReview: $tree->where('schema_status', CategorySchemaStatus::Draft)->count(),
        );

        $searchPattern = LiteralLikePattern::containing($filters->search ?? '');
        $query = CentralCategory::query()
            ->withCount(['products', 'attributeAssignments'])
            ->addSelect([
                'facets_count' => FacetDefinition::query()->selectRaw('count(*)')->whereColumn('category_id', 'central_categories.id'),
                'sites_count' => SiteCategory::query()->selectRaw('count(distinct site_id)')->whereColumn('central_category_id', 'central_categories.id')->whereIn('site_id', array_keys($sites)),
            ])
            ->when($filters->search !== null, fn ($query) => $query->where(fn ($query) => $query
                ->whereRaw("name LIKE ? ESCAPE '!'", [$searchPattern])->orWhereRaw("slug LIKE ? ESCAPE '!'", [$searchPattern])))
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->when($filters->schemaStatus !== null, fn ($query) => $query->where('schema_status', $filters->schemaStatus))
            ->when($filters->level !== null, fn ($query) => $query->whereIn('id', array_keys(array_filter($depths, fn ($depth) => $depth === $filters->level))))
            ->when($filters->siteId !== null, fn ($query) => $query->whereIn('id', SiteCategory::query()->select('central_category_id')->where('site_id', $filters->siteId)));
        if ($filters->translation !== null) {
            $this->translationScope($query, $filters->localeId === null ? $localeIds : [$filters->localeId], $filters->translation);
        }
        $sort = match ($filters->sort) {
            'products' => 'products_count',
            'attributes' => 'attribute_assignments_count',
            'facets' => 'facets_count',
            'sites' => 'sites_count',
            default => $filters->sort,
        };
        $categories = $query->orderBy($sort, $filters->direction)->orderBy('central_categories.id')
            ->paginate($filters->perPage, ['*'], 'page', $page)->withQueryString();
        $translations = CategoryTranslation::query()->whereIn('category_id', $categories->getCollection()->modelKeys())
            ->whereIn('locale_id', $localeIds)->get(['category_id', 'locale_id', 'status'])->groupBy('category_id');
        $categories->setCollection($categories->getCollection()->map(function (CentralCategory $category) use ($tree, $depths, $locales, $translations, $filters): CategoryListRow {
            /** @var Collection<int, CategoryTranslation> $rows */
            $rows = $translations->get($category->id, collect());
            $missing = count($locales) - $rows->where('status', '!=', TranslationStatus::Missing)->count();
            $outdated = $rows->where('status', TranslationStatus::Outdated)->count();

            return new CategoryListRow(
                category: $category,
                depth: $depths[$category->id],
                parentName: $tree->get($category->parent_id)?->name,
                products: (int) $category->getAttribute('products_count'),
                attributes: (int) $category->getAttribute('attribute_assignments_count'),
                facets: (int) $category->getAttribute('facets_count'),
                sites: (int) $category->getAttribute('sites_count'),
                localeTotal: count($locales),
                covered: count($locales) - $missing - $outdated,
                missing: $missing,
                outdated: $outdated,
                localeStatus: $filters->localeId === null ? null : ($rows->firstWhere('locale_id', $filters->localeId)->status ?? TranslationStatus::Missing),
            );
        }));
        $levels = array_values(array_unique(array_filter($depths, static fn ($depth) => $depth !== null)));
        sort($levels);
        $options = [];
        foreach ($levels as $level) {
            $options[$level] = $level === 0 ? 'Root' : 'Level '.$level;
        }

        return new CategoryListReadModelData($categories, $summary, $options, $locales, $sites);
    }

    /** @param Builder<CentralCategory> $query
     * @param  list<int>  $localeIds
     * @return Builder<CentralCategory>
     */
    private function translationScope(Builder $query, array $localeIds, string $state): Builder
    {
        if ($localeIds === []) {
            return $query->whereIn('central_categories.id', []);
        }
        if ($state === 'outdated') {
            return $query->whereHas('translations', fn ($translations) => $translations->whereIn('locale_id', $localeIds)->where('status', TranslationStatus::Outdated));
        }
        // Missing is an absent row or explicit Missing. Outdated remains distinct.
        $missingLocales = Locale::query()->select('id')->whereIn('id', $localeIds)->whereNotExists(
            CategoryTranslation::query()->select('id')->whereColumn('locale_id', 'locales.id')
                ->whereColumn('category_id', 'central_categories.id')->where('status', '!=', TranslationStatus::Missing)->toBase(),
        )->toBase();
        if ($state === 'missing') {
            return $query->whereExists($missingLocales);
        }

        return $query->whereNotExists($missingLocales)->whereDoesntHave('translations', fn ($translations) => $translations->whereIn('locale_id', $localeIds)->where('status', TranslationStatus::Outdated));
    }

    /** @param Collection<int, CentralCategory> $tree
     * @return array<int, int|null>
     */
    private function depths(Collection $tree): array
    {
        $depths = [];
        foreach ($tree as $category) {
            $path = [];
            $current = (int) $category->id;
            while ($current !== null && ! array_key_exists($current, $depths)) {
                if (isset($path[$current]) || ! $tree->has($current)) {
                    foreach ($path as $id => $_) {
                        $depths[$id] = null;
                    }

                    continue 2;
                }
                $path[$current] = true;
                $current = $tree->get($current)?->parent_id;
            }
            $depth = $current === null ? -1 : $depths[$current];
            foreach (array_reverse(array_keys($path)) as $id) {
                $depths[$id] = $depth === null ? null : ++$depth;
            }
        }

        return $depths;
    }
}
