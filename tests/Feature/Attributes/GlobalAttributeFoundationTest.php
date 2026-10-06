<?php

namespace Tests\Feature\Attributes;

use App\Actions\CategorySchema\AssignAttributeToCategoryAction;
use App\Actions\CategorySchema\CreateGlobalAttributeDefinitionAction;
use App\Actions\CategorySchema\DeleteAttributeOptionAction;
use App\Actions\CategorySchema\DeleteAttributeSectionAction;
use App\Actions\CategorySchema\MoveCategoryAttributeAssignmentAction;
use App\Actions\CategorySchema\UnassignAttributeFromCategoryAction;
use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Actions\CategorySchema\UpdateAttributeOptionAction;
use App\Actions\CategorySchema\UpdateCategoryAttributeAssignmentAction;
use App\Actions\CategorySchema\UpdateGlobalAttributeDefinitionAction;
use App\Actions\Translations\SaveAttributeTranslationAction;
use App\Enums\CategorySchemaStatus;
use App\Exceptions\CategorySchema\CannotDeleteAttributeSectionException;
use App\Models\AttributeDisplayRule;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\ContentRelation;
use App\Models\FacetDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\Imports\ImportSource;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\Locale;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Models\User;
use App\Queries\Attributes\AttributeGlobalizationDiagnosticsQuery;
use App\Services\Audit\AuditRecorder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\Support\LegacyAttributeEvidence;
use Tests\TestCase;

final class GlobalAttributeFoundationTest extends TestCase
{
    use LegacyAttributeEvidence;
    use RefreshDatabase;

    private function createDefinition(): AttributeDefinition
    {
        return app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'screen_size', 'name' => 'Screen size', 'data_type' => 'decimal'], User::factory()->centralAdmin()->create());
    }

    public function test_global_definition_reuse_with_local_flags_sections_and_shared_options(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'display_type', 'name' => 'Display', 'data_type' => 'enum'], $actor);
        $option = AttributeOption::factory()->for($definition, 'attribute')->create(['code' => 'OLED']);
        $a = CentralCategory::factory()->create();
        $b = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($a, 'category')->create();
        $one = app(AssignAttributeToCategoryAction::class)->handle($a, $definition, ['attribute_section_id' => $section->id, 'position' => 7, 'is_required' => true, 'is_visible' => false, 'is_searchable' => true, 'is_sortable' => true], 1, $actor);
        $two = app(AssignAttributeToCategoryAction::class)->handle($b, $definition, [], 1, $actor);
        self::assertNull($definition->central_category_id);
        self::assertSame('display_type', $definition->code);
        self::assertSame($section->id, $one->attribute_section_id);
        self::assertNull($two->attribute_section_id);
        self::assertFalse($two->is_required);
        self::assertTrue($two->is_visible);
        self::assertFalse($two->is_searchable);
        self::assertFalse($two->is_sortable);
        self::assertSame($option->id, $two->definition->options->sole()->id);
        self::assertSame(2, $a->fresh()->schema_revision);
        self::assertSame(2, $b->fresh()->schema_revision);
        self::assertSame(2, $definition->assignments()->count());
    }

    public function test_duplicate_assignment_is_rejected_by_domain_and_database(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        try {
            app(AssignAttributeToCategoryAction::class)->handle($assignment->category, $assignment->definition, [], 1, $actor);
            self::fail('Duplicate accepted');
        } catch (ValidationException) {
            self::assertSame(0, AuditLogEntry::query()->count());
        }
        $this->expectException(QueryException::class);
        DB::transaction(fn () => CategoryAttributeAssignment::query()->create($assignment->only(['central_category_id', 'attribute_definition_id'])));
    }

    public function test_cross_category_section_is_rejected_by_domain_and_database(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $foreign = AttributeSection::factory()->create();
        try {
            app(MoveCategoryAttributeAssignmentAction::class)->handle($assignment, $foreign->id, 0, 1, $actor);
            self::fail('Cross-Category Section accepted');
        } catch (ValidationException) {
            self::assertSame(1, $assignment->category->fresh()->schema_revision);
        }
        $this->expectException(QueryException::class);
        DB::transaction(fn () => $assignment->forceFill(['attribute_section_id' => $foreign->id])->saveOrFail());
    }

    public function test_temporary_section_removal_recognizes_new_global_assignments(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $section = AttributeSection::factory()->for($assignment->category, 'category')->create();
        $assignment->forceFill(['attribute_section_id' => $section->id])->saveOrFail();
        $this->expectException(CannotDeleteAttributeSectionException::class);
        app(DeleteAttributeSectionAction::class)->handle($section, $actor);
    }

    public function test_local_configuration_noop_stale_write_and_archived_guard(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = $this->createDefinition();
        $a = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $b = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $action = app(UpdateCategoryAttributeAssignmentAction::class);
        $action->handle($a, ['is_visible' => false], 1, $actor);
        self::assertSame(2, $a->category->fresh()->schema_revision);
        self::assertSame(1, $b->category->fresh()->schema_revision);
        $count = AuditLogEntry::query()->count();
        $action->handle($a, ['is_visible' => false], 2, $actor);
        self::assertSame($count, AuditLogEntry::query()->count());
        try {
            $action->handle($a, ['is_required' => true], 1, $actor);
            self::fail('Stale write accepted');
        } catch (ValidationException) {
            self::assertFalse($a->fresh()->is_required);
        }
        $a->category->forceFill(['schema_status' => CategorySchemaStatus::Archived])->save();
        $this->expectException(ValidationException::class);
        $action->handle($a, ['is_visible' => true], 2, $actor);
    }

    public function test_move_compacts_only_the_local_groups_and_invalidates_once(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $a = CategoryAttributeAssignment::factory()->create(['position' => 0]);
        $b = CategoryAttributeAssignment::factory()->for($a->category, 'category')->create(['position' => 1]);
        $foreign = CategoryAttributeAssignment::factory()->create(['position' => 9]);
        $section = AttributeSection::factory()->for($a->category, 'category')->create();
        app(MoveCategoryAttributeAssignmentAction::class)->handle($a, $section->id, 0, 1, $actor);
        self::assertSame(0, $b->fresh()->position);
        self::assertSame(9, $foreign->fresh()->position);
        self::assertSame(2, $a->category->fresh()->schema_revision);
        self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.attribute.moved')->count());
    }

    public function test_global_edit_fans_out_noop_does_nothing_and_archived_category_rolls_everything_back(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = $this->createDefinition();
        $a = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $b = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        foreach ([$a, $b] as $assignment) {
            $assignment->category->forceFill(['schema_status' => 'approved', 'schema_reviewed_revision' => 1, 'schema_approved_revision' => 1, 'schema_approved_by_user_id' => $actor->id, 'schema_approved_at' => now()])->save();
        }
        $action = app(UpdateGlobalAttributeDefinitionAction::class);
        $action->handle($definition, ['name' => 'Diagonal screen size'], $actor);
        foreach ([$a, $b] as $assignment) {
            $category = $assignment->category->fresh();
            self::assertSame(2, $category->schema_revision);
            self::assertSame('draft', $category->schema_status->value);
            self::assertNull($category->schema_approved_at);
            self::assertNull($category->schema_reviewed_revision);
        }
        $count = AuditLogEntry::query()->count();
        $action->handle($definition, ['name' => 'Diagonal screen size'], $actor);
        self::assertSame($count, AuditLogEntry::query()->count());
        $b->category->forceFill(['schema_status' => 'archived'])->save();
        try {
            $action->handle($definition, ['name' => 'Unsafe'], $actor);
            self::fail('Archived fan-out accepted');
        } catch (ValidationException) {
            self::assertSame('Diagonal screen size', $definition->fresh()->name);
            self::assertSame(2, $a->category->fresh()->schema_revision);
            self::assertSame($count, AuditLogEntry::query()->count());
        }
    }

    public function test_audit_failure_rolls_back_canonical_edit_and_entire_fanout(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = $this->createDefinition();
        $assignments = CategoryAttributeAssignment::factory()->count(2)->for($definition, 'definition')->create();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->andThrow(new RuntimeException('audit unavailable'));
        try {
            app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, ['name' => 'Changed'], $actor);
            self::fail('Audit failure ignored');
        } catch (RuntimeException) {
            self::assertSame('Screen size', $definition->fresh()->name);
            foreach ($assignments as $assignment) {
                self::assertSame(1, $assignment->category->fresh()->schema_revision);
            }
            self::assertSame(1, AuditLogEntry::query()->count());
        }
    }

    #[DataProvider('dangerousFields')]
    public function test_dangerous_semantic_changes_require_explicit_migration(string $field, mixed $value): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = $this->createDefinition();
        $assignment = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $product = CentralProduct::factory()->for($assignment->category, 'category')->create();
        $fact = CentralProductAttributeValue::factory()->create(['central_product_id' => $product->id, 'attribute_definition_id' => $definition->id, 'value_type' => 'decimal', 'value_number' => '12.500000']);
        $before = $fact->fresh()->getRawOriginal();
        try {
            app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, [$field => $value], $actor);
            self::fail('Semantic change accepted');
        } catch (ValidationException $e) {
            self::assertStringContainsString('Explicit migration required', $e->getMessage());
            self::assertSame($before, $fact->fresh()->getRawOriginal());
            self::assertSame(1, $assignment->category->fresh()->schema_revision);
        }
    }

    public static function dangerousFields(): array
    {
        return [['code', 'new_screen_size'], ['data_type', 'string']];
    }

    public function test_measured_numeric_definition_and_database_pair_constraints(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $dimension = MeasurementDimension::factory()->create();
        $unit = MeasurementUnit::factory()->for($dimension, 'dimension')->create();
        $definition = app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'length', 'name' => 'Length', 'data_type' => 'decimal', 'measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $unit->id], $actor);
        self::assertSame($dimension->id, $definition->measurement_dimension_id);
        self::assertSame($unit->code, $definition->canonicalMeasurementUnit?->code);
        $this->expectException(QueryException::class);
        DB::transaction(fn () => $definition->forceFill(['data_type' => 'string'])->saveOrFail());
    }

    public function test_incompatible_measurement_pair_and_incomplete_pair_fail_domain_validation(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $dimension = MeasurementDimension::factory()->create();
        $foreignUnit = MeasurementUnit::factory()->create();
        foreach ([['measurement_dimension_id' => $dimension->id], ['measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $foreignUnit->id]] as $pair) {
            try {
                app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'bad', 'name' => 'Bad', 'data_type' => 'integer', ...$pair], $actor);
                self::fail('Invalid pair accepted');
            } catch (ValidationException) {
                self::assertSame(0, AttributeDefinition::query()->count());
            }
        }
    }

    public function test_unresolved_legacy_measurement_evidence_remains_a_cutover_blocker(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['code' => 'legacy_size', 'data_type' => 'decimal']);
        $this->recordLegacyEvidence();
        AttributeDefinitionCrosswalk::query()->whereKey($definition->id)->update(['measurement_status' => 'unknown_catalog_code']);
        $report = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
        self::assertSame([$definition->id], $report['blockers']['measurement_mapping_failures']);
        self::assertFalse($report['cutover_ready']);
    }

    public function test_durable_legacy_inventory_count_survives_safe_membership_removal(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->assignedTo($category)->create(['code' => 'legacy_label']);
        $this->recordLegacyEvidence();
        app(UnassignAttributeFromCategoryAction::class)->handle($definition->assignments()->sole(), 1, $actor);
        self::assertNull($definition->fresh()->central_category_id);
        $report = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
        self::assertSame(1, $report['legacy_definition_count']);
        self::assertTrue($report['cutover_ready']);
        self::assertSame($definition->id, AttributeDefinitionCrosswalk::query()->sole()->legacy_definition_id);
    }

    public function test_unassignment_preserves_global_meaning_and_options_but_product_dependency_blocks_removal(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        app(UnassignAttributeFromCategoryAction::class)->handle($assignment, 1, $actor);
        self::assertSame(1, AttributeDefinition::query()->count());
        self::assertSame(0, CategoryAttributeAssignment::query()->count());
        $assignment = CategoryAttributeAssignment::factory()->create();
        $product = CentralProduct::factory()->for($assignment->category, 'category')->create();
        CentralProductAttributeValue::factory()->create(['central_product_id' => $product->id, 'attribute_definition_id' => $assignment->attribute_definition_id]);
        $this->expectException(ValidationException::class);
        app(UnassignAttributeFromCategoryAction::class)->handle($assignment, 1, $actor);
    }

    public function test_new_global_code_is_unique_and_unresolved_legacy_codes_are_reserved(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        AttributeDefinition::factory()->create(['code' => 'shared']);
        $this->expectException(ValidationException::class);
        app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'shared', 'name' => 'Shared', 'data_type' => 'string'], $actor);
    }

    public function test_legacy_option_identity_cannot_change_or_disappear_under_existing_product_facts(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->assignedTo($category)->create(['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($definition, 'attribute')->create(['code' => 'oled']);
        $product = CentralProduct::factory()->for($definition->assignments()->firstOrFail()->category, 'category')->create();
        $fact = CentralProductAttributeValue::factory()->create(['central_product_id' => $product->id, 'attribute_definition_id' => $definition->id, 'value_type' => 'enum', 'value_enum_code' => 'oled']);
        $before = $fact->fresh()->getRawOriginal();
        foreach (['rename', 'delete'] as $operation) {
            try {
                if ($operation === 'rename') {
                    app(UpdateAttributeOptionAction::class)->handle($option, ['code' => 'new_code', 'label' => $option->label], $actor);
                } else {
                    app(DeleteAttributeOptionAction::class)->handle($option, $actor);
                }
                self::fail('Referenced option identity changed');
            } catch (ValidationException) {
                self::assertSame('oled', $option->fresh()->code);
                self::assertSame($before, $fact->fresh()->getRawOriginal());
                self::assertSame(0, AuditLogEntry::query()->count());
            }
        }
        app(UpdateAttributeOptionAction::class)->handle($option, ['code' => 'oled', 'label' => $option->label, 'is_visible' => false], $actor);
        self::assertFalse($option->fresh()->is_visible);
        self::assertSame($before, $fact->fresh()->getRawOriginal());
    }

    public function test_clean_diagnostic_is_deterministic_readonly_and_command_succeeds(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        CategoryAttributeAssignment::factory()->create();
        $query = app(AttributeGlobalizationDiagnosticsQuery::class);
        $before = AuditLogEntry::query()->count();
        $one = $query->report($actor);
        self::assertTrue($one['cutover_ready']);
        self::assertSame($one, $query->report($actor));
        self::assertSame($before, AuditLogEntry::query()->count());
        $this->artisan('catalog:diagnose-attribute-globalization', ['--actor' => $actor->id])->assertSuccessful();
    }

    public function test_content_references_use_global_identity_without_category_ownership(): void
    {
        $definition = AttributeDefinition::factory()->create(['code' => 'global_size']);
        $relation = ContentRelation::factory()->create(['related_type' => 'attribute', 'related_id' => $definition->id]);
        self::assertSame($definition->id, $relation->related_id);
        self::assertArrayNotHasKey('central_category_id', $definition->getAttributes());
    }

    public function test_temporary_definition_editor_audits_canonical_change_and_invalidates_all_assignments(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->assignedTo($category)->create(['code' => 'size', 'data_type' => 'decimal']);
        $this->recordLegacyEvidence();
        $other = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $action = app(UpdateAttributeDefinitionAction::class);
        $data = ['code' => 'size', 'name' => 'Canonical size', 'data_type' => 'decimal'];
        $action->handle($definition, $data, $actor);
        self::assertSame(2, $definition->assignments()->firstOrFail()->category->fresh()->schema_revision);
        self::assertSame(2, $other->category->fresh()->schema_revision);
        $event = AuditLogEntry::query()->where('action', 'catalog.attribute.updated')->sole();
        self::assertSame(2, $event->after_json['affected_category_count']);
        self::assertSame(['name'], $event->after_json['changed_fields']);
        $action->handle($definition, $data, $actor);
        self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.attribute.updated')->count());
    }

    #[DataProvider('dependencyOwners')]
    public function test_canonical_type_edit_is_blocked_by_each_existing_dependency_owner(string $owner): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->assignedTo($category)->create(['code' => 'meaning', 'data_type' => 'decimal']);
        $this->recordLegacyEvidence();
        $assignment = $definition->assignments()->sole();
        match ($owner) {
            'options' => AttributeOption::factory()->for($definition, 'attribute')->create(),
            'mapping' => AttributeMapping::query()->create(['import_source_id' => ImportSource::factory()->create()->id, 'category_id' => $assignment->central_category_id, 'raw_key' => 'Size', 'normalized_raw_key' => 'size', 'category_attribute_assignment_id' => $assignment->id, 'status' => 'reviewed']),
            'draft_id' => NormalizedProductDraft::factory()->create(['category_id' => $assignment->central_category_id, 'attributes_json' => [['attribute_definition_id' => $definition->id]]]),
            'draft_code' => NormalizedProductDraft::factory()->create(['category_id' => $assignment->central_category_id, 'attributes_json' => [['code' => $definition->code]]]),
            'facet' => FacetDefinition::factory()->create(['category_id' => $assignment->central_category_id, 'category_attribute_assignment_id' => $assignment->id, 'source_type' => 'attribute']),
            'content' => ContentRelation::factory()->create(['related_type' => 'attribute', 'related_id' => $definition->id]),
            'display' => AttributeDisplayRule::factory()->create(['attribute_definition_id' => $definition->id]),
            'multiple_assignments' => CategoryAttributeAssignment::factory()->for($definition, 'definition')->create(),
            default => throw new \InvalidArgumentException('Unknown dependency fixture.'),
        };
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Explicit migration required');
        app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, ['data_type' => 'integer'], $actor);
    }

    public static function dependencyOwners(): array
    {
        return array_map(fn ($owner) => [$owner], ['options', 'mapping', 'draft_id', 'draft_code', 'facet', 'content', 'display', 'multiple_assignments']);
    }

    public function test_measurement_changes_with_product_facts_require_explicit_migration(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = $this->createDefinition();
        $assignment = CategoryAttributeAssignment::factory()->for($definition, 'definition')->create();
        $product = CentralProduct::factory()->for($assignment->category, 'category')->create();
        CentralProductAttributeValue::factory()->create(['central_product_id' => $product->id, 'attribute_definition_id' => $definition->id]);
        $dimension = MeasurementDimension::factory()->create();
        $unit = MeasurementUnit::factory()->for($dimension, 'dimension')->create();
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Explicit migration required');
        app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, ['measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $unit->id], $actor);
    }

    public function test_assignment_audit_failure_rolls_back_membership_and_revision(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = $this->createDefinition();
        $category = CentralCategory::factory()->create();
        $this->mock(AuditRecorder::class)->shouldReceive('record')->andThrow(new RuntimeException('audit unavailable'));
        try {
            app(AssignAttributeToCategoryAction::class)->handle($category, $definition, [], 1, $actor);
            self::fail('Failed audit committed');
        } catch (RuntimeException) {
            self::assertSame(0, CategoryAttributeAssignment::query()->count());
            self::assertSame(1, $category->fresh()->schema_revision);
        }
    }

    public function test_new_ownership_writes_block_unsafe_expand_downgrade(): void
    {
        $this->createDefinition();
        $migration = require database_path('migrations/2026_10_05_000001_expand_global_attribute_ownership.php');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Global ownership has new writes');
        $migration->down();
    }

    public function test_database_pair_membership_and_complete_pair_are_enforced(): void
    {
        $definition = $this->createDefinition();
        $dimension = MeasurementDimension::factory()->create();
        $foreignUnit = MeasurementUnit::factory()->create();
        foreach ([['measurement_dimension_id' => $dimension->id], ['measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $foreignUnit->id]] as $fields) {
            try {
                DB::transaction(fn () => AttributeDefinition::query()->whereKey($definition->id)->update($fields));
                self::fail('Invalid database pair persisted');
            } catch (QueryException) {
                self::assertNull($definition->fresh()->measurement_dimension_id);
            }
        }
    }

    public function test_mapping_assignment_category_and_definition_membership_are_enforced(): void
    {
        $category = CentralCategory::factory()->create();
        $otherCategory = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->assignedTo($category)->create();
        $other = AttributeDefinition::factory()->assignedTo($otherCategory)->create();
        $this->recordLegacyEvidence();
        $mapping = AttributeMapping::query()->create(['import_source_id' => ImportSource::factory()->create()->id, 'category_id' => $category->id, 'raw_key' => 'Size', 'normalized_raw_key' => 'size', 'category_attribute_assignment_id' => $definition->assignments()->sole()->id]);
        $this->expectException(QueryException::class);
        DB::transaction(fn () => $mapping->forceFill(['category_attribute_assignment_id' => $other->assignments()->sole()->id])->saveOrFail());
    }

    public function test_selected_crosswalk_product_merge_conflict_is_reported_without_changing_values(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $one = AttributeDefinition::factory()->assignedTo($category)->create();
        $two = AttributeDefinition::factory()->assignedTo($category)->create();
        $this->recordLegacyEvidence();
        $product = CentralProduct::factory()->for($category, 'category')->create();
        foreach ([$one, $two] as $definition) {
            CentralProductAttributeValue::factory()->create(['central_product_id' => $product->id, 'attribute_definition_id' => $definition->id]);
        }
        AttributeDefinitionCrosswalk::query()->whereKey($two->id)->update(['canonical_definition_id' => $one->id]);
        $report = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
        self::assertCount(1, $report['blockers']['product_value_merge_conflicts']);
        self::assertSame(2, CentralProductAttributeValue::query()->count());
        self::assertFalse($report['cutover_ready']);
    }

    public function test_collision_free_typed_owners_enforce_locale_id_uniqueness_without_removing_old_keys(): void
    {
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->global()->create(['data_type' => 'enum']);
        $section = AttributeSection::factory()->for($category, 'category')->create();
        $option = AttributeOption::factory()->for($definition, 'attribute')->create();
        $unit = MeasurementUnit::factory()->create();
        $locale = Locale::factory()->create();
        foreach ([['category_translations', 'category_id', $category->id], ['attribute_translations', 'attribute_definition_id', $definition->id], ['attribute_section_translations', 'attribute_section_id', $section->id], ['attribute_option_translations', 'attribute_option_id', $option->id], ['unit_translations', 'measurement_unit_id', $unit->id]] as [$table, $owner, $id]) {
            DB::table($table)->insert([$owner => $id, 'locale_id' => $locale->id, 'locale' => $locale->code]);
            try {
                DB::transaction(fn () => DB::table($table)->insert([$owner => $id, 'locale_id' => $locale->id, 'locale' => 'legacy_alias']));
                self::fail('Duplicate Locale identity persisted');
            } catch (QueryException) {
                self::assertSame(1, DB::table($table)->count());
            }
        }
    }

    public function test_locale_code_refresh_updates_the_same_global_attribute_translation_identity(): void
    {
        $definition = AttributeDefinition::factory()->global()->create();
        $locale = Locale::factory()->create();
        $save = app(SaveAttributeTranslationAction::class);
        $translation = $save->handle($definition, $locale, ['label' => 'Preserved text']);
        $locale->forceFill(['code' => 'renamed_locale'])->saveOrFail();
        $updated = $save->handle($definition, $locale, ['label' => 'Preserved text']);
        self::assertSame($translation->id, $updated->id);
        self::assertSame('renamed_locale', $updated->locale);
        self::assertSame('Preserved text', $updated->label);
        self::assertSame(1, $definition->translations()->count());
    }

    public function test_disabled_and_readonly_actors_fail_closed_for_new_writes_and_reads(): void
    {
        $disabled = User::factory()->centralAdmin()->disabled()->create();
        try {
            app(AttributeGlobalizationDiagnosticsQuery::class)->report($disabled);
            self::fail('Disabled schema read admitted');
        } catch (AuthorizationException) {
            self::assertSame(0, AuditLogEntry::query()->count());
        }
        config(['cataloghub_permissions.roles.catalog_editor' => ['central.panel.access', 'central.page.access', 'catalog.schema.manage']]);
        $actor = User::factory()->create(['role' => 'catalog_editor']);
        self::assertTrue(app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor)['cutover_ready']);
        $this->expectException(AuthorizationException::class);
        app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'test', 'name' => 'Test', 'data_type' => 'string'], $actor);
    }

    #[DataProvider('deniedActors')]
    public function test_reads_and_writes_fail_closed_for_non_schema_actors(string $state): void
    {
        $actor = $state === 'guest' ? null : User::factory()->create(['role' => $state]);
        try {
            app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
            self::fail('Unauthorized schema read admitted');
        } catch (AuthorizationException) {
            self::assertSame(0, AuditLogEntry::query()->count());
        }
        $this->expectException(AuthorizationException::class);
        app(CreateGlobalAttributeDefinitionAction::class)->handle(['code' => 'test', 'name' => 'Test', 'data_type' => 'string'], $actor);
    }

    public static function deniedActors(): array
    {
        return array_map(fn ($state) => [$state], ['guest', 'site_admin', 'translator', 'moderator', 'catalog_editor']);
    }
}
