<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CategoryFormReadModelData;
use App\Data\CentralCatalog\CategoryFormSummary;
use App\Enums\CentralCategoryStatus;
use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\Site;
use Illuminate\Support\Collection;

/**
 * Read model for the CA-018 Category Create/Edit form.
 *
 * One minimal tree snapshot supplies parent options, ancestry and sibling order;
 * hierarchy scope revisions are read (never created) as expected-state tokens.
 * Purely read-only: no positions are normalized and no scope rows are initialized.
 */
final readonly class CategoryFormReadModelQuery
{
    public function forCreate(): CategoryFormReadModelData
    {
        $tree = $this->tree();

        return new CategoryFormReadModelData(
            category: null,
            parentOptions: $this->parentOptions($tree, []),
            scopeRevisions: $this->scopeRevisions($tree),
            parentId: null,
            ancestors: [],
            hierarchyAvailable: true,
            siblings: [],
            siblingOrderContiguous: true,
            summary: null,
            translationLocaleId: null,
        );
    }

    public function forEdit(CentralCategory $category): CategoryFormReadModelData
    {
        $tree = $this->tree();
        [$ancestors, $hierarchyAvailable] = $this->ancestors($tree, $category);
        $siblings = $tree->where('parent_id', $category->parent_id)->sortBy([['position', 'asc'], ['id', 'asc']])->values();
        $positions = $siblings->pluck('position')->map(fn ($position): int => (int) $position)->all();

        return new CategoryFormReadModelData(
            category: $category,
            parentOptions: $this->parentOptions($tree, [$category->id, ...$this->descendantIds($tree, (int) $category->id)]),
            scopeRevisions: $this->scopeRevisions($tree),
            parentId: $category->parent_id,
            ancestors: $ancestors,
            hierarchyAvailable: $hierarchyAvailable,
            siblings: $siblings->map(fn (CentralCategory $sibling): array => ['id' => (int) $sibling->id, 'name' => $sibling->name])->all(),
            siblingOrderContiguous: $positions === ($positions === [] ? [] : range(0, count($positions) - 1)),
            summary: new CategoryFormSummary(
                sections: $category->attributeSections()->count(),
                assignments: $category->attributeAssignments()->count(),
                products: $category->products()->count(),
                selectedSites: Site::query()->selectingCategory($category->id)->count(),
            ),
            translationLocaleId: Locale::query()->active()->orderByDesc('is_default')->orderBy('position')->orderBy('code')->orderBy('id')->value('id'),
        );
    }

    /** @return Collection<int, CentralCategory> */
    private function tree(): Collection
    {
        return CentralCategory::query()->orderBy('id')->get(['id', 'parent_id', 'name', 'slug', 'status', 'position'])->keyBy('id');
    }

    /**
     * @param  Collection<int, CentralCategory>  $tree
     * @return array<string, int>
     */
    private function scopeRevisions(Collection $tree): array
    {
        $stored = CategoryHierarchyScope::query()->pluck('revision', 'scope_key');
        $revisions = ['' => (int) ($stored['root'] ?? 0)];
        foreach ($tree->keys() as $id) {
            $revisions[(string) $id] = (int) ($stored[CategoryHierarchyScope::keyFor((int) $id)] ?? 0);
        }

        return $revisions;
    }

    /**
     * Tree-ordered eligible parents labelled by their full path; excluded IDs are self and descendants.
     *
     * @param  Collection<int, CentralCategory>  $tree
     * @param  list<int>  $excluded
     * @return list<array{value: string, label: string, search: string}>
     */
    private function parentOptions(Collection $tree, array $excluded): array
    {
        $children = $tree->groupBy(fn (CentralCategory $category): string => (string) $category->parent_id);
        $options = [];
        $visited = [];
        $walk = function (?int $parentId, array $path) use (&$walk, &$options, &$visited, $children, $excluded): void {
            foreach ($children->get((string) $parentId, collect())->sortBy([['position', 'asc'], ['id', 'asc']]) as $category) {
                $id = (int) $category->id;
                if (isset($visited[$id]) || in_array($id, $excluded, true)) {
                    continue;
                }
                $visited[$id] = true;
                $categoryPath = [...$path, $category->name];
                $status = $category->status === CentralCategoryStatus::Active ? '' : ' · '.$category->status->label();
                $options[] = [
                    'value' => (string) $id,
                    'label' => implode(' / ', $categoryPath).$status,
                    'search' => implode(' ', [...$categoryPath, $category->slug, '#'.$id]),
                ];
                $walk($id, $categoryPath);
            }
        };
        $walk(null, []);
        // Categories unreachable from a root (missing parent or cycle) stay selectable by ID; actions reject unsafe ancestry.
        foreach ($tree as $category) {
            $id = (int) $category->id;
            if (! isset($visited[$id]) && ! in_array($id, $excluded, true)) {
                $options[] = ['value' => (string) $id, 'label' => $category->name.' (hierarchy unavailable)', 'search' => $category->name.' '.$category->slug.' #'.$id];
            }
        }
        // Names are not unique: identical paths are disambiguated by the stable ID.
        $labels = array_count_values(array_column($options, 'label'));

        return array_map(static fn (array $option): array => $labels[$option['label']] > 1
            ? [...$option, 'label' => $option['label'].' · #'.$option['value']] : $option, $options);
    }

    /**
     * @param  Collection<int, CentralCategory>  $tree
     * @return list<int>
     */
    private function descendantIds(Collection $tree, int $categoryId): array
    {
        $children = $tree->groupBy(fn (CentralCategory $category): string => (string) $category->parent_id);
        $found = [];
        $queue = [$categoryId];
        while ($queue !== []) {
            $current = array_shift($queue);
            foreach ($children->get((string) $current, collect()) as $child) {
                $id = (int) $child->id;
                if ($id !== $categoryId && ! isset($found[$id])) {
                    $found[$id] = true;
                    $queue[] = $id;
                }
            }
        }

        return array_keys($found);
    }

    /**
     * @param  Collection<int, CentralCategory>  $tree
     * @return array{0: list<array{id: int, name: string}>, 1: bool}
     */
    private function ancestors(Collection $tree, CentralCategory $category): array
    {
        $ancestors = [];
        $seen = [$category->id => true];
        $parentId = $category->parent_id;
        while ($parentId !== null) {
            if (isset($seen[$parentId]) || ! $tree->has($parentId)) {
                return [[], false];
            }
            $parent = $tree->get($parentId);
            $ancestors[] = ['id' => (int) $parent->id, 'name' => $parent->name];
            $seen[$parentId] = true;
            $parentId = $parent->parent_id;
        }

        return [array_reverse($ancestors), true];
    }
}
