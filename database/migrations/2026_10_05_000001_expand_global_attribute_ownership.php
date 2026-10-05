<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LOCAL_FIELDS = ['attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable'];

    /** @var array<string, array{dimension_id: int|null, unit_id: int|null, status: string}> */
    private array $measurementCache = [];

    public function up(): void
    {
        Schema::create('attribute_identity_scopes', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedBigInteger('write_epoch')->default(0);
        });
        DB::table('attribute_identity_scopes')->insert(['id' => 1, 'write_epoch' => 0]);
        Schema::table('attribute_definitions', function (Blueprint $table): void {
            $table->unsignedBigInteger('central_category_id')->nullable()->change();
            $table->string('canonical_code')->nullable()->unique();
            $table->foreignId('measurement_dimension_id')->nullable()->constrained('measurement_dimensions')->restrictOnDelete();
            $table->foreignId('canonical_measurement_unit_id')->nullable();
            $table->foreign(['canonical_measurement_unit_id', 'measurement_dimension_id'], 'attribute_measurement_pair_fk')
                ->references(['id', 'dimension_id'])->on('measurement_units')->restrictOnDelete();
        });
        Schema::create('category_attribute_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_category_id')->constrained('central_categories')->restrictOnDelete();
            $table->foreignId('attribute_definition_id')->constrained('attribute_definitions')->restrictOnDelete();
            $table->foreignId('attribute_section_id')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('is_sortable')->default(false);
            $table->timestamps();
            $table->unique(['central_category_id', 'attribute_definition_id'], 'category_attribute_assignment_unique');
            $table->unique(['id', 'central_category_id', 'attribute_definition_id'], 'assignment_identity_category_definition_unique');
            $table->unique(['id', 'central_category_id'], 'assignment_identity_category_unique');
            $table->foreign(['attribute_section_id', 'central_category_id'], 'assignment_section_category_fk')
                ->references(['id', 'central_category_id'])->on('attribute_sections')->restrictOnDelete();
            $table->index(['central_category_id', 'attribute_section_id', 'position'], 'assignment_category_section_position_idx');
        });
        Schema::create('attribute_definition_crosswalks', function (Blueprint $table): void {
            // Legacy identities intentionally survive independently of old FK owners.
            $table->unsignedBigInteger('legacy_definition_id')->primary();
            $table->unsignedBigInteger('legacy_category_id')->nullable();
            $table->string('legacy_code');
            $table->foreignId('canonical_definition_id')->nullable()->constrained('attribute_definitions')->restrictOnDelete();
            $table->string('canonical_code')->nullable();
            $table->string('identity_status');
            $table->string('measurement_status');
            $table->string('reason')->nullable();
            $table->unsignedInteger('identity_version')->default(1);
        });
        Schema::create('attribute_option_crosswalks', function (Blueprint $table): void {
            $table->unsignedBigInteger('legacy_option_id')->primary();
            $table->unsignedBigInteger('legacy_definition_id')->index();
            $table->string('legacy_code');
            $table->foreignId('canonical_option_id')->nullable()->constrained('attribute_options')->nullOnDelete();
            $table->string('canonical_code')->nullable();
            $table->string('status');
        });
        Schema::table('attribute_mappings', function (Blueprint $table): void {
            $table->foreignId('category_attribute_assignment_id')->nullable();
            $table->index(['category_attribute_assignment_id', 'category_id', 'attribute_definition_id'], 'attribute_mapping_assignment_membership_idx');
            $table->foreign(['category_attribute_assignment_id', 'category_id'], 'attribute_mapping_assignment_category_fk')
                ->references(['id', 'central_category_id'])->on('category_attribute_assignments')->restrictOnDelete();
            $table->foreign(['category_attribute_assignment_id', 'category_id', 'attribute_definition_id'], 'attribute_mapping_assignment_definition_fk')
                ->references(['id', 'central_category_id', 'attribute_definition_id'])->on('category_attribute_assignments')->restrictOnDelete();
        });
        Schema::table('normalized_product_drafts', function (Blueprint $table): void {
            $table->unsignedInteger('attribute_identity_version')->default(1);
        });
        $this->backfill();
        $this->constraints(true);
    }

    public function down(): void
    {
        $this->measurementCache = [];
        if ((int) DB::table('attribute_identity_scopes')->where('id', 1)->value('write_epoch') !== 0
            || DB::table('attribute_definitions')->whereNull('central_category_id')->exists()
            || DB::table('category_attribute_assignments as a')->join('attribute_definitions as d', 'd.id', '=', 'a.attribute_definition_id')->whereColumn('a.central_category_id', '!=', 'd.central_category_id')->exists()) {
            throw new RuntimeException('Global ownership has new writes. Restore/replay with an inverse crosswalk; automatic downgrade is unsafe.');
        }
        foreach (DB::table('category_attribute_assignments as a')->join('attribute_definitions as d', 'd.id', '=', 'a.attribute_definition_id')->select(['a.*', 'd.attribute_section_id as legacy_section_id', 'd.position as legacy_position', 'd.is_required as legacy_required', 'd.is_visible as legacy_visible', 'd.is_searchable as legacy_searchable', 'd.is_sortable as legacy_sortable'])->cursor() as $assignment) {
            foreach (['attribute_section_id' => 'legacy_section_id', 'position' => 'legacy_position', 'is_required' => 'legacy_required', 'is_visible' => 'legacy_visible', 'is_searchable' => 'legacy_searchable', 'is_sortable' => 'legacy_sortable'] as $field => $legacyField) {
                if ($assignment->$field !== $assignment->$legacyField) {
                    throw new RuntimeException('Assignment authority differs from legacy compatibility. Automatic downgrade is unsafe.');
                }
            }
        }
        $crosswalkIds = DB::table('attribute_definition_crosswalks')->pluck('legacy_definition_id')->flip()->all();
        foreach (DB::table('attribute_definitions')->orderBy('id')->cursor() as $definition) {
            $expected = $this->measurement($definition->dimension, $definition->canonical_unit, $definition->data_type);
            $hasRelationalAuthority = $definition->measurement_dimension_id !== null || $definition->canonical_measurement_unit_id !== null
                || isset($crosswalkIds[$definition->id]);
            if ($hasRelationalAuthority && ($definition->measurement_dimension_id !== $expected['dimension_id'] || $definition->canonical_measurement_unit_id !== $expected['unit_id'])) {
                throw new RuntimeException('Relational measurement authority has new writes. Automatic downgrade is unsafe.');
            }
        }
        $this->constraints(false);
        Schema::table('normalized_product_drafts', fn (Blueprint $table) => $table->dropColumn('attribute_identity_version'));
        Schema::table('attribute_mappings', function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['category_attribute_assignment_id', 'category_id', 'attribute_definition_id'] : 'attribute_mapping_assignment_definition_fk');
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['category_attribute_assignment_id', 'category_id'] : 'attribute_mapping_assignment_category_fk');
            $table->dropIndex('attribute_mapping_assignment_membership_idx');
            $table->dropColumn('category_attribute_assignment_id');
        });
        Schema::drop('attribute_option_crosswalks');
        Schema::drop('attribute_definition_crosswalks');
        Schema::drop('category_attribute_assignments');
        Schema::table('attribute_definitions', function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['canonical_measurement_unit_id', 'measurement_dimension_id'] : 'attribute_measurement_pair_fk');
            $table->dropForeign(['measurement_dimension_id']);
            $table->dropUnique(['canonical_code']);
            $table->dropColumn(['canonical_code', 'measurement_dimension_id', 'canonical_measurement_unit_id']);
            $table->unsignedBigInteger('central_category_id')->nullable(false)->change();
        });
        Schema::drop('attribute_identity_scopes');
    }

    /** Frozen expand behavior: application backfill services may evolve independently. */
    private function backfill(): void
    {
        $this->measurementCache = [];
        DB::transaction(function (): void {
            $duplicateCodes = DB::table('attribute_definitions')->select('code')->groupBy('code')->havingRaw('COUNT(*) > 1');
            $duplicateIds = DB::table('attribute_definitions as definitions')
                ->joinSub($duplicateCodes, 'duplicates', fn ($join) => $join->on('definitions.code', '=', 'duplicates.code'))
                ->pluck('definitions.id')->flip()->all();
            DB::table('attribute_definitions')->whereNotNull('central_category_id')->orderBy('id')->chunkById(500, function ($definitions) use ($duplicateIds): void {
                $assignments = [];
                $crosswalks = [];
                foreach ($definitions as $definition) {
                    $row = (array) $definition;
                    $assignments[] = [
                        'central_category_id' => $definition->central_category_id, 'attribute_definition_id' => $definition->id,
                        ...array_intersect_key($row, array_flip(self::LOCAL_FIELDS)),
                        'created_at' => $definition->created_at, 'updated_at' => $definition->updated_at,
                    ];
                    $duplicate = isset($duplicateIds[$definition->id]);
                    $measurement = $this->measurement($definition->dimension, $definition->canonical_unit, $definition->data_type);
                    DB::table('attribute_definitions')->where('id', $definition->id)->update([
                        'canonical_code' => $duplicate ? null : $definition->code,
                        'measurement_dimension_id' => $measurement['dimension_id'], 'canonical_measurement_unit_id' => $measurement['unit_id'],
                    ]);
                    $crosswalks[] = [
                        'legacy_definition_id' => $definition->id, 'legacy_category_id' => $definition->central_category_id, 'legacy_code' => $definition->code,
                        'canonical_definition_id' => $duplicate ? null : $definition->id, 'canonical_code' => $duplicate ? null : $definition->code,
                        'identity_status' => $duplicate ? 'unresolved_code_collision' : 'identity_preserved',
                        'measurement_status' => $measurement['status'], 'reason' => $duplicate ? 'Explicit canonical selection or distinct approved code required.' : null,
                    ];
                }
                DB::table('category_attribute_assignments')->insertOrIgnore($assignments);
                DB::table('attribute_definition_crosswalks')->insertOrIgnore($crosswalks);
            });
            DB::table('attribute_options')->orderBy('id')->chunkById(500, function ($options): void {
                DB::table('attribute_option_crosswalks')->insertOrIgnore($options->map(fn ($option) => [
                    'legacy_option_id' => $option->id, 'legacy_definition_id' => $option->attribute_definition_id, 'legacy_code' => $option->code,
                    'canonical_option_id' => $option->id, 'canonical_code' => $option->code, 'status' => 'identity_preserved',
                ])->all());
            });
            DB::table('attribute_mappings')->whereNotNull('attribute_definition_id')->whereNull('category_attribute_assignment_id')->orderBy('id')->chunkById(500, function ($mappings): void {
                $assignments = DB::table('category_attribute_assignments')->whereIn('attribute_definition_id', $mappings->pluck('attribute_definition_id'))
                    ->get()->keyBy(fn ($assignment) => $assignment->central_category_id.':'.$assignment->attribute_definition_id);
                foreach ($mappings as $mapping) {
                    $assignment = $assignments->get($mapping->category_id.':'.$mapping->attribute_definition_id);
                    if ($assignment !== null) {
                        DB::table('attribute_mappings')->where('id', $mapping->id)->update(['category_attribute_assignment_id' => $assignment->id]);
                    }
                }
            });
        });
    }

    /** @return array{dimension_id: int|null, unit_id: int|null, status: string} */
    private function measurement(?string $dimension, ?string $unit, string $type): array
    {
        $key = json_encode([$dimension, $unit, $type], JSON_THROW_ON_ERROR);
        if (isset($this->measurementCache[$key])) {
            return $this->measurementCache[$key];
        }
        $status = 'unmeasured';
        $dimensionId = null;
        $unitId = null;
        if ($dimension !== null || $unit !== null) {
            $dimensionRow = DB::table('measurement_dimensions')->where('code', $dimension)->first();
            $unitRow = DB::table('measurement_units')->where('code', $unit)->first();
            $status = match (true) {
                $dimension === null || $unit === null => 'incomplete_pair',
                $dimensionRow === null || $unitRow === null => 'unknown_catalog_code',
                (int) $unitRow->dimension_id !== (int) $dimensionRow->id => 'incompatible_pair',
                ! in_array($type, ['integer', 'decimal'], true) => 'measured_non_numeric',
                default => 'resolved_exact',
            };
            if ($status === 'resolved_exact') {
                $dimensionId = (int) $dimensionRow->id;
                $unitId = (int) $unitRow->id;
            }
        }

        return $this->measurementCache[$key] = ['dimension_id' => $dimensionId, 'unit_id' => $unitId, 'status' => $status];
    }

    private function constraints(bool $create): void
    {
        $checks = [
            'attribute_definitions' => [
                'attribute_measurement_shape_check' => "((measurement_dimension_id IS NULL AND canonical_measurement_unit_id IS NULL) OR (measurement_dimension_id IS NOT NULL AND canonical_measurement_unit_id IS NOT NULL AND data_type IN ('integer', 'decimal')))",
                'global_attribute_code_check' => '(central_category_id IS NOT NULL OR (canonical_code IS NOT NULL AND canonical_code = code))',
            ],
            'attribute_mappings' => [
                'attribute_mapping_assignment_shape_check' => '(category_attribute_assignment_id IS NULL OR attribute_definition_id IS NOT NULL)',
            ],
        ];
        foreach ($checks as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                if (DB::getDriverName() === 'sqlite') {
                    // SQLite cannot ADD CHECK to existing tables. Equivalent INSERT/UPDATE guards.
                    foreach (['INSERT', 'UPDATE'] as $operation) {
                        $trigger = $name.'_'.strtolower($operation);
                        if ($create) {
                            $newExpression = preg_replace('/\b(measurement_dimension_id|canonical_measurement_unit_id|data_type|central_category_id|canonical_code|code|category_attribute_assignment_id|attribute_definition_id)\b/', 'NEW.$1', $expression);
                            DB::statement("CREATE TRIGGER {$trigger} BEFORE {$operation} ON {$table} WHEN NOT {$newExpression} BEGIN SELECT RAISE(ABORT, '{$name}'); END");
                        } else {
                            DB::statement("DROP TRIGGER IF EXISTS {$trigger}");
                        }
                    }
                } elseif ($create) {
                    DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$name} CHECK {$expression}");
                } else {
                    DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$name}");
                }
            }
        }
    }
};
