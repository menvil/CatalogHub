<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Data\CentralCatalog\CentralCategoryExpectedState;
use App\Data\CentralCatalog\CentralCategorySaveData;
use App\Models\CentralCatalog\CentralCategory;
use App\Queries\CentralCatalog\CategorySlugImpactQuery;
use App\Services\Categories\CategoryHierarchy;

/**
 * Builds a CA-018 Edit submission from the persisted state an editor would load,
 * then applies requested changes. Supported keys: name, slug, parent_id,
 * display_position, fingerprint, acknowledged.
 */
trait BuildsCategorySaveData
{
    /** @param array<string, mixed> $changes */
    protected function categorySaveData(CentralCategory $category, array $changes = []): CentralCategorySaveData
    {
        $category = $category->fresh() ?? $category;
        $hierarchy = app(CategoryHierarchy::class);
        $siblings = CentralCategory::query()->where('parent_id', $category->parent_id)->orderBy('position')->orderBy('id')->pluck('id')
            ->map(fn ($id): int => (int) $id)->all();
        $parentId = array_key_exists('parent_id', $changes) ? $changes['parent_id'] : $category->parent_id;
        $slug = (string) ($changes['slug'] ?? $category->slug);
        $fingerprint = array_key_exists('fingerprint', $changes) ? $changes['fingerprint']
            : ($slug === $category->slug ? null : app(CategorySlugImpactQuery::class)->assess($category, $category->slug, $slug)->fingerprint);

        return new CentralCategorySaveData(
            name: (string) ($changes['name'] ?? $category->name),
            slug: $slug,
            parentId: $parentId === null ? null : (int) $parentId,
            expected: new CentralCategoryExpectedState($category->name, $category->slug, $category->parent_id),
            originalScopeRevision: $hierarchy->revision($category->parent_id),
            destinationScopeRevision: $hierarchy->revision($parentId === null ? null : (int) $parentId),
            siblingIds: $siblings,
            displayPosition: (int) ($changes['display_position'] ?? array_search((int) $category->id, $siblings, true)),
            slugImpactFingerprint: is_string($fingerprint) ? $fingerprint : null,
            slugImpactAcknowledged: (bool) ($changes['acknowledged'] ?? false),
        );
    }

    /**
     * HTTP form payload equivalent to categorySaveData().
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    protected function categoryUpdatePayload(CentralCategory $category, array $changes = []): array
    {
        $data = $this->categorySaveData($category, $changes);

        return array_filter([
            'name' => $data->name,
            'slug' => $data->slug,
            'parent_id' => $data->parentId,
            'expected_parent_id' => $data->expected->parentId,
            'expected_name' => $data->expected->name,
            'expected_slug' => $data->expected->slug,
            'current_scope_revision' => $data->originalScopeRevision,
            'destination_scope_revision' => $data->destinationScopeRevision,
            'sibling_ids' => $data->siblingIds,
            'display_position' => $data->displayPosition,
            'slug_impact_fingerprint' => $data->slugImpactFingerprint,
            'slug_impact_acknowledged' => $data->slugImpactAcknowledged ? '1' : null,
        ], static fn (mixed $value, string $key): bool => $value !== null || in_array($key, ['parent_id', 'expected_parent_id'], true), ARRAY_FILTER_USE_BOTH);
    }
}
