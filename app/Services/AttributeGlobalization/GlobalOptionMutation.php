<?php

namespace App\Services\AttributeGlobalization;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Enums\SchemaMutationOrigin;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\SchemaRevision;
use Closure;
use Illuminate\Support\Facades\DB;

final class GlobalOptionMutation
{
    /** @param Closure(): AttributeOption $write */
    public function run(AttributeDefinition $definition, ?AttributeOption $option, Closure $write, ?User $actor): AttributeOption
    {
        $actor = app(CategoryAccess::class)->authorize(Permission::CatalogSchemaManage, $actor);

        return DB::transaction(function () use ($definition, $option, $write, $actor): AttributeOption {
            app(AttributeIdentityLock::class)->acquire();
            $ids = $definition->assignments()->orderBy('central_category_id')->pluck('central_category_id')->all();
            $categories = app(CategoryLock::class)->acquire($ids);
            $definition = AttributeDefinition::query()->whereKey($definition->id)->lockForUpdate()->firstOrFail();
            foreach ($categories as $category) {
                app(SchemaRevision::class)->assertMutable($category);
            }
            $before = $option === null ? null : AttributeOption::query()->whereKey($option->id)->where('attribute_definition_id', $definition->id)->lockForUpdate()->firstOrFail()->only(['id', 'code', 'label', 'position', 'is_visible']);
            $result = $write();
            $after = $result->only(['id', 'code', 'label', 'position', 'is_visible']);
            if ($before === $after) {
                return $result;
            }
            foreach ($categories as $category) {
                app(SchemaRevision::class)->invalidate($category, $option === null ? SchemaMutationOrigin::OptionCreated : SchemaMutationOrigin::OptionUpdated, $result->id, $actor);
            }
            $snapshot = fn ($row) => $row === null ? null : ['option_id' => $row['id'], 'definition_id' => $definition->id,
                'code' => $row['code'], 'label' => $row['label'], 'position' => $row['position'], 'is_visible' => $row['is_visible'], 'affected_category_count' => count($ids),
                'changed_fields' => array_keys(array_filter($after, fn ($value, $key) => $before === null || $value !== $before[$key], ARRAY_FILTER_USE_BOTH))];
            app(AuditRecorder::class)->record($option === null ? AuditAction::CatalogAttributeOptionCreated : AuditAction::CatalogAttributeOptionUpdated,
                AuditContext::Central, $actor, $definition, null, $snapshot($before), $snapshot($after));

            return $result;
        }, 3);
    }
}
