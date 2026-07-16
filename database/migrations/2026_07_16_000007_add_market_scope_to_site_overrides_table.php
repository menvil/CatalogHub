<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_overrides', function (Blueprint $table): void {
            $table->dropUnique('site_overrides_scope_unique');
            $table->foreignId('site_id')->nullable()->change();
            $table->foreignId('market_id')->nullable()->after('site_id')->constrained()->cascadeOnDelete();
            $table->unique(
                ['site_id', 'entity_type', 'entity_id', 'field', 'locale_code'],
                'site_overrides_scope_unique',
            );
            $table->unique(
                ['market_id', 'entity_type', 'entity_id', 'field', 'locale_code'],
                'site_overrides_market_scope_unique',
            );
        });
    }

    public function down(): void
    {
        DB::table('site_overrides')->whereNull('site_id')->delete();

        Schema::table('site_overrides', function (Blueprint $table): void {
            $table->dropUnique('site_overrides_scope_unique');
            $table->dropUnique('site_overrides_market_scope_unique');
            $table->dropConstrainedForeignId('market_id');
            $table->foreignId('site_id')->nullable(false)->change();
            $table->unique(
                ['site_id', 'entity_type', 'entity_id', 'field', 'locale_code'],
                'site_overrides_scope_unique',
            );
        });
    }
};
