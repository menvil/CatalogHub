<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_category_id')->constrained('central_categories')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable();
            $table->string('code');
            $table->string('name');
            $table->unsignedInteger('position')->default(0);
            $table->string('display_style')->default('table');
            $table->boolean('is_collapsible')->default(true);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->unique(['central_category_id', 'code']);
            $table->unique(['id', 'central_category_id']);
            $table->index(['central_category_id', 'position']);
            $table->foreign(['parent_id', 'central_category_id'], 'attr_section_parent_cat_fk')
                ->references(['id', 'central_category_id'])
                ->on('attribute_sections')
                ->restrictOnDelete();
        });
        if (DB::getDriverName() === 'sqlite') {
            foreach (['INSERT', 'UPDATE'] as $operation) {
                $name = 'attribute_section_flat_check_'.strtolower($operation);
                DB::statement("CREATE TRIGGER {$name} BEFORE {$operation} ON attribute_sections WHEN NEW.parent_id IS NOT NULL BEGIN SELECT RAISE(ABORT, 'attribute_section_flat_check'); END");
            }
        } else {
            DB::statement('ALTER TABLE attribute_sections ADD CONSTRAINT attribute_section_flat_check CHECK (parent_id IS NULL)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_sections');
    }
};
