<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attribute_identity_scopes', function (Blueprint $table): void {
            $table->unsignedInteger('consumer_version')->default(1);
            $table->unsignedBigInteger('cutover_write_epoch')->nullable();
            $table->boolean('cutover_ready_for_finalization')->default(false);
        });
        Schema::table('facet_definitions', function (Blueprint $table): void {
            $table->foreignId('category_attribute_assignment_id')->nullable();
            $table->index(['category_attribute_assignment_id', 'category_id'], 'facet_assignment_category_idx');
            $table->foreign(['category_attribute_assignment_id', 'category_id'], 'facet_assignment_category_fk')
                ->references(['id', 'central_category_id'])->on('category_attribute_assignments')->restrictOnDelete();
        });
        DB::table('facet_definitions')->where('source_type', 'attribute')->orderBy('id')->chunkById(500, function ($facets): void {
            $memberships = DB::table('category_attribute_assignments')->whereIn('attribute_definition_id', $facets->pluck('attribute_definition_id'))
                ->get()->keyBy(fn ($row) => $row->central_category_id.':'.$row->attribute_definition_id);
            foreach ($facets as $facet) {
                if ($assignment = $memberships->get($facet->category_id.':'.$facet->attribute_definition_id)) {
                    DB::table('facet_definitions')->where('id', $facet->id)->update(['category_attribute_assignment_id' => $assignment->id]);
                }
            }
        });
        Schema::create('category_comparison_attributes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('central_category_id')->constrained('central_categories')->restrictOnDelete();
            $table->foreignId('category_attribute_assignment_id');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->unique(['central_category_id', 'category_attribute_assignment_id'], 'comparison_category_assignment_unique');
            $table->foreign(['category_attribute_assignment_id', 'central_category_id'], 'comparison_assignment_category_fk')
                ->references(['id', 'central_category_id'])->on('category_attribute_assignments')->restrictOnDelete();
        });
        Schema::create('schema_consumer_decisions', function (Blueprint $table): void {
            $table->id();
            $table->string('owner_type');
            $table->unsignedBigInteger('owner_id');
            $table->string('decision');
            $table->string('plan_hash', 64);
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['owner_type', 'owner_id']);
        });
        Schema::create('schema_consumer_reconciliation_plans', function (Blueprint $table): void {
            $table->string('plan_hash', 64)->primary();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('decision_count');
            $table->timestamp('applied_at');
        });
        Schema::create('schema_consumer_section_decisions', function (Blueprint $table): void {
            $table->unsignedBigInteger('section_id');
            $table->string('plan_hash', 64);
            $table->unsignedBigInteger('legacy_parent_id')->nullable();
            $table->unsignedInteger('legacy_position');
            $table->unsignedInteger('target_position');
            $table->primary(['section_id', 'plan_hash']);
        });
        foreach (['site_product_projections', 'site_category_projections', 'site_search_documents'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->unsignedInteger('attribute_identity_version')->default(1);
                $table->unsignedBigInteger('schema_revision')->default(0);
            });
        }
    }

    public function down(): void
    {
        if ((int) DB::table('attribute_identity_scopes')->value('consumer_version') !== 1) {
            throw new RuntimeException('Undo the guarded consumer cutover before removing its foundation.');
        }
        foreach (['site_product_projections', 'site_category_projections', 'site_search_documents'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['attribute_identity_version', 'schema_revision']));
        }
        Schema::drop('schema_consumer_section_decisions');
        Schema::drop('schema_consumer_reconciliation_plans');
        Schema::drop('schema_consumer_decisions');
        Schema::drop('category_comparison_attributes');
        Schema::table('facet_definitions', function (Blueprint $table): void {
            $table->dropForeign(DB::getDriverName() === 'sqlite' ? ['category_attribute_assignment_id', 'category_id'] : 'facet_assignment_category_fk');
            $table->dropIndex('facet_assignment_category_idx');
            $table->dropColumn('category_attribute_assignment_id');
        });
        Schema::table('attribute_identity_scopes', fn (Blueprint $table) => $table->dropColumn(['consumer_version', 'cutover_write_epoch', 'cutover_ready_for_finalization']));
    }
};
