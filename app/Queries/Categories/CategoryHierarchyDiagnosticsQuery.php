<?php

namespace App\Queries\Categories;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\SiteCategory;

final class CategoryHierarchyDiagnosticsQuery
{
    /** @return array<string, mixed> */
    public function report(): array
    {
        $categories = CentralCategory::query()->orderBy('id')->get(['id', 'parent_id', 'position', 'status', 'schema_status']);
        $parents = $categories->pluck('parent_id', 'id')->all();
        $issues = [];
        $cycles = [];
        foreach ($parents as $id => $parentId) {
            if ($parentId !== null && ! array_key_exists($parentId, $parents)) {
                $issues[] = ['code' => 'missing_parent', 'category_id' => $id, 'parent_id' => $parentId];
            }
            $path = [];
            $node = $id;
            while ($node !== null) {
                if (! array_key_exists($node, $parents)) {
                    break;
                }
                if (isset($path[$node])) {
                    $cycle = array_slice(array_keys($path), $path[$node]);
                    sort($cycle, SORT_NUMERIC);
                    $cycles[implode(',', $cycle)] = $cycle;
                    break;
                }
                $path[$node] = count($path);
                $node = $parents[$node];
            }
        }
        ksort($cycles, SORT_STRING);
        foreach ($cycles as $cycle) {
            $issues[] = ['code' => count($cycle) === 1 ? 'self_parent' : 'cycle', 'category_ids' => $cycle];
        }
        foreach ($categories->groupBy(fn ($row) => $row->parent_id === null ? 'root' : 'parent:'.$row->parent_id) as $scope => $siblings) {
            $positions = $siblings->sortBy([['position', 'asc'], ['id', 'asc']])->pluck('position')->all();
            if ($positions !== range(0, $siblings->count() - 1)) {
                $issues[] = ['code' => 'non_contiguous_positions', 'scope_key' => $scope, 'category_ids' => $siblings->pluck('id')->all(), 'positions' => $positions];
            }
        }

        return [
            'category_ids' => $categories->pluck('id')->all(),
            'root_ids' => $categories->whereNull('parent_id')->pluck('id')->all(),
            'nested_ids' => $categories->whereNotNull('parent_id')->pluck('id')->all(),
            'archived_ids' => $categories->filter(fn ($row) => $row->getRawOriginal('status') === 'archived')->pluck('id')->all(),
            'with_product_ids' => CentralCategory::query()->has('products')->orderBy('id')->pluck('id')->all(),
            'site_selected_ids' => SiteCategory::query()->orderBy('central_category_id')->distinct()->pluck('central_category_id')->all(),
            'schema_states' => $categories->groupBy(fn ($row) => $row->getRawOriginal('schema_status'))->map(fn ($rows) => $rows->pluck('id')->all())->sortKeys()->all(),
            'nested_section_ids' => AttributeSection::query()->whereNotNull('parent_id')->orderBy('id')->pluck('id')->all(),
            'issues' => $issues,
        ];
    }
}
