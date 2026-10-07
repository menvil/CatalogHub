<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('data_type')->default('string');
            $table->foreignId('measurement_dimension_id')->nullable()->constrained('measurement_dimensions')->restrictOnDelete();
            $table->foreignId('canonical_measurement_unit_id')->nullable();
            $table->foreign(['canonical_measurement_unit_id', 'measurement_dimension_id'], 'attribute_measurement_pair_fk')
                ->references(['id', 'dimension_id'])->on('measurement_units')->restrictOnDelete();
            $table->timestamps();
        });
        $expression = "((measurement_dimension_id IS NULL AND canonical_measurement_unit_id IS NULL) OR (measurement_dimension_id IS NOT NULL AND canonical_measurement_unit_id IS NOT NULL AND data_type IN ('integer', 'decimal')))";
        if (DB::getDriverName() === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $name = 'attribute_measurement_shape_check_'.strtolower($operation);
                $guard = preg_replace('/\\b(measurement_dimension_id|canonical_measurement_unit_id|data_type)\\b/', 'NEW.$1', $expression);
                DB::statement("CREATE TRIGGER {$name} BEFORE {$operation} ON attribute_definitions WHEN NOT {$guard} BEGIN SELECT RAISE(ABORT, 'attribute_measurement_shape_check'); END");
            }
        } else {
            DB::statement("ALTER TABLE attribute_definitions ADD CONSTRAINT attribute_measurement_shape_check CHECK {$expression}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_definitions');
    }
};
