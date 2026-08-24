<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_assets', fn (Blueprint $table) => $table->unique(['disk', 'original_path'], 'media_assets_disk_path_unique'));
        $driver = DB::getDriverName();
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement("CREATE UNIQUE INDEX media_variants_context_unique ON media_variants (media_asset_id, variant_type, COALESCE(locale, ''), COALESCE(site_id, 0), COALESCE(market_id, 0))");

            return;
        }
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE media_variants ADD COLUMN context_locale_key VARCHAR(255) GENERATED ALWAYS AS (COALESCE(locale, '')) STORED, ADD COLUMN context_site_key BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(site_id, 0)) STORED, ADD COLUMN context_market_key BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(market_id, 0)) STORED, ADD UNIQUE INDEX media_variants_context_unique (media_asset_id, variant_type, context_locale_key, context_site_key, context_market_key)");
        }
    }

    public function down(): void
    {
        Schema::table('media_assets', fn (Blueprint $table) => $table->dropUnique('media_assets_disk_path_unique'));
        $driver = DB::getDriverName();
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            DB::statement('DROP INDEX media_variants_context_unique');
        }
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE media_variants DROP INDEX media_variants_context_unique, DROP COLUMN context_locale_key, DROP COLUMN context_site_key, DROP COLUMN context_market_key');
        }
    }
};
