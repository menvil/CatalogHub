<?php

namespace App\Actions\CategorySchema;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class SaveCategoryComparisonAction
{
    /** @param list<array<string, mixed>> $rows */
    public function handle(CentralCategory $category, array $rows, int $expectedRevision, ?User $actor = null): void
    {
        app(SchemaRevision::class)->mutate($category->id, SchemaMutationOrigin::ComparisonConfigured, null, function () use ($category, $rows, $expectedRevision, $actor): void {
            $category = CentralCategory::query()->findOrFail($category->id);
            app(SchemaRevision::class)->expect($category, $expectedRevision);
            Validator::make(['rows' => $rows], ['rows' => ['array', 'max:1000'], 'rows.*' => ['array:category_attribute_assignment_id,position,is_visible'],
                'rows.*.category_attribute_assignment_id' => ['required', 'integer', 'distinct'], 'rows.*.position' => ['required', 'integer', 'min:0', 'distinct'], 'rows.*.is_visible' => ['required', 'boolean']])->validate();
            $ids = array_column($rows, 'category_attribute_assignment_id');
            if (CategoryAttributeAssignment::query()->where('central_category_id', $category->id)->whereIn('id', $ids)->count() !== count($ids)) {
                throw ValidationException::withMessages(['rows' => 'Every comparison row must reference an assignment of the same Category.']);
            }
            $existing = CategoryComparisonAttribute::query()->where('central_category_id', $category->id)->orderBy('position')->orderBy('id')->lockForUpdate()->get();
            $normalize = fn ($items) => collect($items)->map(fn ($row) => ['category_attribute_assignment_id' => (int) $row['category_attribute_assignment_id'], 'position' => (int) $row['position'], 'is_visible' => (bool) $row['is_visible']])->sortBy('category_attribute_assignment_id')->values()->all();
            if ($normalize($existing->toArray()) === $normalize($rows)) {
                return;
            }
            CategoryComparisonAttribute::query()->where('central_category_id', $category->id)->whereNotIn('category_attribute_assignment_id', $ids)->delete();
            foreach ($rows as $row) {
                $record = $existing->firstWhere('category_attribute_assignment_id', $row['category_attribute_assignment_id']) ?? new CategoryComparisonAttribute(['central_category_id' => $category->id]);
                $record->fill($row);
                if ($record->isDirty()) {
                    $record->saveOrFail();
                }
            }
            $snapshot = fn ($items) => ['assignment_ids' => array_slice(collect($items)->pluck('category_attribute_assignment_id')->sort()->values()->all(), 0, 100), 'row_count' => count($items), 'changed_fields' => ['assignment', 'position', 'is_visible']];
            app(AuditRecorder::class)->record(AuditAction::CatalogCategoryComparisonUpdated, AuditContext::Central, $actor ?? auth()->user(), $category, null, $snapshot($existing), $snapshot($rows));
        }, $actor);
    }
}
