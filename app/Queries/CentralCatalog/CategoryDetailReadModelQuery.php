<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CategoryDetailReadModelData;
use App\Data\CentralCatalog\CategoryDetailSchemaSummary;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Site;
use App\Models\User;
use App\Services\CategorySchema\CategorySchemaValidator;

final readonly class CategoryDetailReadModelQuery
{
    public const SITE_LIMIT = 8;

    public function __construct(private CategorySchemaValidator $validator, private CategoryDetailTranslationQuery $translations, private CategoryActivityQuery $activity) {}

    public function forCategory(CentralCategory $category, ?int $localeId = null): CategoryDetailReadModelData
    {
        $translations = $this->translations->forCategory($category, $localeId);
        $tree = CentralCategory::query()->orderBy('id')->get(['id', 'parent_id', 'name'])->keyBy('id');
        $ancestors = [];
        $seen = [$category->id => true];
        $parentId = $category->parent_id;
        $hierarchyAvailable = true;
        while ($parentId !== null) {
            if (isset($seen[$parentId]) || ! $tree->has($parentId)) {
                $hierarchyAvailable = false;
                $ancestors = [];
                break;
            }
            $parent = $tree->get($parentId);
            $ancestors[] = ['id' => (int) $parent->id, 'name' => $parent->name];
            $seen[$parentId] = true;
            $parentId = $parent->parent_id;
        }
        $issues = $this->validator->validate($category)->issues();
        $actors = User::query()->whereIn('id', array_filter([$category->schema_reviewed_by_user_id, $category->schema_approved_by_user_id]))->pluck('name', 'id');
        $schema = new CategoryDetailSchemaSummary(
            sections: $category->attributeSections->count(), attributes: $category->attributeAssignments->count(),
            required: $category->attributeAssignments->where('is_required', true)->count(),
            facets: FacetDefinition::query()->where('category_id', $category->id)->count(),
            comparison: CategoryComparisonAttribute::query()->where('central_category_id', $category->id)->count(),
            reviewer: $category->schema_reviewed_by_user_id === null ? null : $actors->get($category->schema_reviewed_by_user_id, 'Actor unavailable'),
            approver: $category->schema_approved_by_user_id === null ? null : $actors->get($category->schema_approved_by_user_id, 'Actor unavailable'),
            issueCount: count($issues), issues: array_slice($issues, 0, 5),
        );
        $siteQuery = Site::query()->administrable()->whereHas('categories', fn ($query) => $query->where('central_category_id', $category->id));

        return new CategoryDetailReadModelData(
            category: $category, ancestors: array_reverse($ancestors), hierarchyAvailable: $hierarchyAvailable,
            schema: $schema, products: $category->products()->count(), selectedSites: (clone $siteQuery)->count(),
            sites: $siteQuery->orderBy('name')->orderBy('id')->limit(self::SITE_LIMIT)->get(['id', 'name', 'status'])
                ->map(fn (Site $site): array => ['name' => $site->name, 'status' => $site->status->label()])->all(),
            translations: $translations, activity: $this->activity->forCategory($category),
        );
    }
}
