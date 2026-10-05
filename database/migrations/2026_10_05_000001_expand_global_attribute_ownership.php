<?php

use App\Services\AttributeGlobalization\LegacyAttributeBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
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
        app(LegacyAttributeBackfill::class)->run();
        $this->constraints(true);
    }

    public function down(): void
    {
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
        foreach (DB::table('attribute_definitions')->orderBy('id')->cursor() as $definition) {
            $expected = app(LegacyAttributeBackfill::class)->measurement($definition->dimension, $definition->canonical_unit, $definition->data_type);
            $hasRelationalAuthority = $definition->measurement_dimension_id !== null || $definition->canonical_measurement_unit_id !== null
                || DB::table('attribute_definition_crosswalks')->where('legacy_definition_id', $definition->id)->exists();
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
