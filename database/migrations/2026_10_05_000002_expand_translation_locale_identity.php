<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OWNERS = ['category_translations' => 'category_id', 'attribute_translations' => 'attribute_definition_id', 'attribute_section_translations' => 'attribute_section_id', 'unit_translations' => 'measurement_unit_id'];

    public function up(): void
    {
        Schema::create('translation_locale_identity_states', function (Blueprint $table): void {
            $table->string('owner_table')->primary();
            $table->boolean('unique_installed');
        });
        foreach (self::OWNERS as $table => $owner) {
            $collisions = DB::table($table)->select([$owner, 'locale_id'])->groupBy($owner, 'locale_id')->havingRaw('COUNT(*) > 1')->exists();
            if (! $collisions) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique([$owner, 'locale_id'], $table.'_owner_locale_id_unique'));
            }
            DB::table('translation_locale_identity_states')->insert(['owner_table' => $table, 'unique_installed' => ! $collisions]);
        }
        // Retain every old code unique key. Conflicts defer the additive key, never select a winner.
    }

    public function down(): void
    {
        foreach (self::OWNERS as $table => $owner) {
            if (DB::table('translation_locale_identity_states')->where('owner_table', $table)->value('unique_installed')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($table.'_owner_locale_id_unique'));
            }
        }
        Schema::drop('translation_locale_identity_states');
    }
};
