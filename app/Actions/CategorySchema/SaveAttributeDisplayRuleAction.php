<?php

namespace App\Actions\CategorySchema;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\Permission;
use App\Enums\SchemaMutationOrigin;
use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\MeasurementUnit;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Audit\AuditRecorder;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryLock;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class SaveAttributeDisplayRuleAction
{
    public function handle(?AttributeDisplayRule $rule, array $data, ?User $actor = null): AttributeDisplayRule
    {
        $actor = app(CategoryAccess::class)->authorize(Permission::CatalogSchemaManage, $actor);

        return DB::transaction(function () use ($rule, $data, $actor): AttributeDisplayRule {
            app(AttributeIdentityLock::class)->acquire();
            $definitionId = $rule === null ? ($data['attribute_definition_id'] ?? null) : $rule->attribute_definition_id;
            if ($rule !== null && isset($data['attribute_definition_id']) && (int) $data['attribute_definition_id'] !== $definitionId) {
                throw ValidationException::withMessages(['attribute_definition_id' => 'Display rule owner is immutable.']);
            }
            $ids = CategoryAttributeAssignment::query()->where('attribute_definition_id', $definitionId)->pluck('central_category_id')->all();
            $categories = app(CategoryLock::class)->acquire($ids);
            $definition = AttributeDefinition::query()->whereKey($definitionId)->lockForUpdate()->firstOrFail();
            foreach ($categories as $category) {
                app(SchemaRevision::class)->assertMutable($category);
            }
            $record = $rule === null ? new AttributeDisplayRule : AttributeDisplayRule::query()->whereKey($rule->id)->lockForUpdate()->firstOrFail();
            $fields = ['attribute_definition_id', 'market_code', 'locale', 'display_unit_id', 'decimals', 'rounding_mode', 'suffix_style'];
            if (array_diff(array_keys($data), $fields) !== []) {
                throw ValidationException::withMessages(['display_rule' => 'Unsupported display rule field.']);
            }
            $input = ['market_code' => '__global', 'locale' => '__global', 'rounding_mode' => 'half_up', 'suffix_style' => 'symbol', ...($record->exists ? $record->only($fields) : []), ...$data];
            $valid = Validator::make($input, ['attribute_definition_id' => ['required', 'integer'], 'market_code' => ['required', 'string', 'max:16'],
                'locale' => ['required', 'string', 'max:16', Rule::unique('attribute_display_rules', 'locale')->where('attribute_definition_id', $definitionId)->where('market_code', $input['market_code'])->ignore($rule?->id)],
                'display_unit_id' => ['nullable', 'integer', 'exists:measurement_units,id'], 'decimals' => ['nullable', 'integer', 'min:0', 'max:255'],
                'rounding_mode' => ['required', 'in:half_up,floor,ceil'], 'suffix_style' => ['required', 'in:symbol,code']])->validate();
            $unit = isset($valid['display_unit_id']) ? MeasurementUnit::query()->findOrFail($valid['display_unit_id']) : null;
            if ($unit !== null && $unit->dimension_id !== $definition->measurement_dimension_id) {
                throw ValidationException::withMessages(['display_unit_id' => 'Display unit must belong to the canonical measurement dimension.']);
            }
            $before = $record->exists ? $record->only(['id', ...$fields]) : null;
            $record->fill($valid);
            if ($record->exists && ! $record->isDirty()) {
                return $record;
            }
            $changed = array_keys($record->getDirty());
            $record->saveOrFail();
            foreach ($categories as $category) {
                app(SchemaRevision::class)->invalidate($category, SchemaMutationOrigin::DisplayRuleConfigured, $record->id, $actor);
            }
            app(AuditRecorder::class)->record(AuditAction::CatalogAttributeDisplayRuleConfigured, AuditContext::Central, $actor, $definition, null, $before,
                [...$record->only(['id', ...$fields]), 'changed_fields' => $changed, 'affected_category_count' => count($categories)]);

            return $record;
        }, 3);
    }
}
