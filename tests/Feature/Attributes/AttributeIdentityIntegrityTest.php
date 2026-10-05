<?php

namespace Tests\Feature\Attributes;

use App\Actions\CategorySchema\AssignAttributeToCategoryAction;
use App\Actions\CategorySchema\CreateAttributeDefinitionAction;
use App\Actions\CategorySchema\CreateAttributeOptionAction;
use App\Actions\CategorySchema\DeleteAttributeOptionAction;
use App\Actions\CategorySchema\UnassignAttributeFromCategoryAction;
use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Actions\CategorySchema\UpdateAttributeOptionAction;
use App\Actions\CategorySchema\UpdateGlobalAttributeDefinitionAction;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeDefinitionCrosswalk;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeOptionCrosswalk;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Queries\Attributes\AttributeGlobalizationDiagnosticsQuery;
use App\Services\AttributeGlobalization\LegacyAttributeBackfill;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AttributeIdentityIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_create_cannot_reuse_a_durable_code_after_rename(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $section = AttributeSection::factory()->create();
        $otherSection = AttributeSection::factory()->create();
        $definition = app(CreateAttributeDefinitionAction::class)->handle($section, ['code' => 'old_code', 'name' => 'Original', 'data_type' => 'string'], $actor);
        app(LegacyAttributeBackfill::class)->run();
        app(UpdateAttributeDefinitionAction::class)->handle($definition, ['code' => 'new_code', 'name' => 'Original', 'data_type' => 'string'], $actor);
        self::assertSame('old_code', AttributeDefinitionCrosswalk::query()->findOrFail($definition->id)->legacy_code);
        $this->assertRejectedWithoutWrites(fn () => app(CreateAttributeDefinitionAction::class)->handle($otherSection, ['code' => 'old_code', 'name' => 'Different meaning', 'data_type' => 'string'], $actor), 'code');
    }

    public function test_live_legacy_duplicate_group_remains_unresolved_without_changing_its_identities(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $first = AttributeDefinition::factory()->create(['code' => 'live_group']);
        app(LegacyAttributeBackfill::class)->run();
        $second = app(CreateAttributeDefinitionAction::class)->handle(AttributeSection::factory()->create(), ['code' => 'live_group', 'name' => 'Other meaning', 'data_type' => 'integer'], $actor);
        self::assertNull($first->fresh()->canonical_code);
        self::assertNull($second->fresh()->canonical_code);
        self::assertSame('unresolved_code_collision', AttributeDefinitionCrosswalk::query()->findOrFail($first->id)->identity_status);
        self::assertFalse(app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor)['cutover_ready']);
    }

    #[DataProvider('identityEdits')]
    public function test_unresolved_definition_rejects_identity_edits_without_partial_promotion(array $edit, bool $legacy): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['code' => 'shared_code', 'name' => 'Original', 'data_type' => 'string']);
        AttributeDefinition::factory()->create(['code' => 'shared_code']);
        app(LegacyAttributeBackfill::class)->run();
        self::assertNull($definition->fresh()->canonical_code);
        self::assertNull(AttributeDefinitionCrosswalk::query()->findOrFail($definition->id)->canonical_definition_id);
        $this->assertRejectedWithoutWrites(fn () => $legacy
            ? app(UpdateAttributeDefinitionAction::class)->handle($definition, ['code' => 'shared_code', 'name' => 'Original', 'data_type' => 'string', ...$edit], $actor)
            : app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, $edit, $actor), 'attribute');
    }

    public static function identityEdits(): array
    {
        return [[['code' => 'new_code'], false], [['data_type' => 'integer'], false], [['code' => 'new_code'], true], [['data_type' => 'integer'], true]];
    }

    public function test_reference_name_edit_keeps_unresolved_identity_and_invalidates_its_category(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['code' => 'shared_code']);
        AttributeDefinition::factory()->create(['code' => 'shared_code']);
        app(LegacyAttributeBackfill::class)->run();
        $before = AttributeDefinitionCrosswalk::query()->findOrFail($definition->id)->getRawOriginal();
        app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, ['name' => 'Clarified reference'], $actor);
        self::assertNull($definition->fresh()->canonical_code);
        self::assertSame($before, AttributeDefinitionCrosswalk::query()->findOrFail($definition->id)->getRawOriginal());
        self::assertSame(2, $definition->category->fresh()->schema_revision);
    }

    public function test_model_canonical_code_cannot_override_unresolved_durable_identity(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['code' => 'shared_code']);
        AttributeDefinition::factory()->create(['code' => 'shared_code']);
        app(LegacyAttributeBackfill::class)->run();
        // Defensive fixture reproduces a partially promoted row from the old writer.
        $definition->forceFill(['canonical_code' => 'partial_identity'])->saveOrFail();
        $category = CentralCategory::factory()->create();
        $this->assertRejectedWithoutWrites(fn () => app(AssignAttributeToCategoryAction::class)->handle($category, $definition, [], 1, $actor), 'attribute_definition_id');
        $this->assertRejectedWithoutWrites(fn () => app(UnassignAttributeFromCategoryAction::class)->handle($definition->assignments()->sole(), 1, $actor), 'assignment');
        $definition->forceFill(['central_category_id' => null, 'attribute_section_id' => null, 'code' => 'partial_identity'])->saveOrFail();
        $before = $this->snapshot();
        $report = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
        self::assertContains($definition->id, $report['unresolved_canonical_identity_rows']);
        self::assertFalse($report['cutover_ready']);
        self::assertSame($before, $this->snapshot());
    }

    public function test_clean_global_definition_without_crosswalk_can_be_assigned(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->global()->create();
        $category = CentralCategory::factory()->create();
        self::assertSame(0, AttributeDefinitionCrosswalk::query()->count());
        app(AssignAttributeToCategoryAction::class)->handle($category, $definition, [], 1, $actor);
        self::assertSame(2, $category->fresh()->schema_revision);
    }

    public function test_unreferenced_option_identity_is_immutable_and_hide_preserves_the_crosswalk(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($definition, 'attribute')->create(['code' => 'old_code']);
        app(LegacyAttributeBackfill::class)->run();
        $crosswalk = AttributeOptionCrosswalk::query()->findOrFail($option->id)->getRawOriginal();
        $this->assertRejectedWithoutWrites(fn () => app(UpdateAttributeOptionAction::class)->handle($option, ['code' => 'new_code', 'label' => 'New'], $actor), 'code');
        $this->assertRejectedWithoutWrites(fn () => app(DeleteAttributeOptionAction::class)->handle($option, $actor), 'option');
        app(UpdateAttributeOptionAction::class)->handle($option, ['code' => 'old_code', 'label' => 'Clarified', 'is_visible' => false], $actor);
        self::assertFalse($option->fresh()->is_visible);
        self::assertSame($crosswalk, AttributeOptionCrosswalk::query()->findOrFail($option->id)->getRawOriginal());
    }

    #[DataProvider('historicalOptionStates')]
    public function test_historical_option_code_cannot_be_reused_and_null_canonical_pointer_blocks_cutover(bool $deleted): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $definition = AttributeDefinition::factory()->create(['data_type' => 'enum']);
        $option = AttributeOption::factory()->for($definition, 'attribute')->create(['code' => 'old_code']);
        app(LegacyAttributeBackfill::class)->run();
        // Persisted states produced by earlier ordinary rename/delete operations.
        if ($deleted) {
            $option->delete();
        } else {
            $option->forceFill(['code' => 'new_code'])->saveOrFail();
            AttributeOptionCrosswalk::query()->whereKey($option->id)->update(['canonical_code' => 'new_code']);
        }
        $this->assertRejectedWithoutWrites(fn () => app(CreateAttributeOptionAction::class)->handle($definition, ['code' => 'old_code', 'label' => 'Different'], $actor), 'code');
        $before = $this->snapshot();
        $report = app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor);
        self::assertSame($deleted, ! $report['cutover_ready']);
        self::assertCount($deleted ? 1 : 0, $report['option_identity_problems']);
        self::assertSame($report, app(AttributeGlobalizationDiagnosticsQuery::class)->report($actor));
        self::assertSame($before, $this->snapshot());
    }

    public static function historicalOptionStates(): array
    {
        return [[false], [true]];
    }

    private function assertRejectedWithoutWrites(Closure $mutation, string $field): void
    {
        $before = $this->snapshot();
        try {
            $mutation();
            self::fail('Unsafe identity mutation succeeded.');
        } catch (ValidationException $exception) {
            self::assertArrayHasKey($field, $exception->errors());
            self::assertSame($before, $this->snapshot());
        }
    }

    private function snapshot(): array
    {
        $rows = [];
        foreach (['attribute_definitions' => 'id', 'attribute_options' => 'id', 'category_attribute_assignments' => 'id', 'attribute_definition_crosswalks' => 'legacy_definition_id', 'attribute_option_crosswalks' => 'legacy_option_id', 'central_categories' => 'id', 'audit_log_entries' => 'id', 'attribute_identity_scopes' => 'id'] as $table => $key) {
            $rows[$table] = DB::table($table)->orderBy($key)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $rows;
    }
}
