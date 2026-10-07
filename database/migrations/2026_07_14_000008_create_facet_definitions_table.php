<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('facet_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('central_categories')->cascadeOnDelete();
            $table->foreignId('category_attribute_assignment_id')->nullable();
            $table->index(['category_attribute_assignment_id', 'category_id'], 'facet_assignment_category_idx');
            $table->foreign(['category_attribute_assignment_id', 'category_id'], 'facet_assignment_category_fk')
                ->references(['id', 'central_category_id'])->on('category_attribute_assignments')->restrictOnDelete();
            $table->string('code')->index();
            $table->string('label_override')->nullable();
            $table->string('facet_type');
            $table->string('source_type');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_collapsible')->default(true);
            $table->boolean('default_collapsed')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->unique(['category_id', 'code']);
            $table->index(['category_id', 'position']);
        });
        $expression = "((source_type = 'attribute' AND category_attribute_assignment_id IS NOT NULL) OR (source_type IN ('brand', 'rating') AND category_attribute_assignment_id IS NULL))";
        if (DB::getDriverName() === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $name = 'facet_assignment_shape_check_'.strtolower($operation);
                $guard = preg_replace('/\\b(source_type|category_attribute_assignment_id)\\b/', 'NEW.$1', $expression);
                DB::statement("CREATE TRIGGER {$name} BEFORE {$operation} ON facet_definitions WHEN NOT {$guard} BEGIN SELECT RAISE(ABORT, 'facet_assignment_shape_check'); END");
            }
        } else {
            DB::statement("ALTER TABLE facet_definitions ADD CONSTRAINT facet_assignment_shape_check CHECK {$expression}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('facet_definitions');
    }
};
