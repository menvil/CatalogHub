<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_asset_id')->constrained('media_assets')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->string('role');
            $table->unsignedInteger('position')->default(0);
            $table->string('locale')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->unsignedBigInteger('market_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('visibility')->default('global');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'role', 'locale'], 'media_assignments_locale_index');
            $table->index(['entity_type', 'entity_id', 'role', 'site_id'], 'media_assignments_site_index');
            $table->index(['entity_type', 'entity_id', 'role', 'market_id'], 'media_assignments_market_index');
            $table->index(['media_asset_id']);
            $table->index(['position']);
        });

        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement(
                "CREATE UNIQUE INDEX media_assignments_primary_context_unique
                ON media_assignments (
                    entity_type,
                    entity_id,
                    role,
                    COALESCE(locale, ''),
                    COALESCE(site_id, 0),
                    COALESCE(market_id, 0)
                )
                WHERE is_primary = true"
            );
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(<<<'SQL'
                ALTER TABLE media_assignments
                ADD COLUMN primary_locale_key VARCHAR(255)
                    GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN COALESCE(locale, '') ELSE NULL END) STORED,
                ADD COLUMN primary_site_key BIGINT UNSIGNED
                    GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN COALESCE(site_id, 0) ELSE NULL END) STORED,
                ADD COLUMN primary_market_key BIGINT UNSIGNED
                    GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN COALESCE(market_id, 0) ELSE NULL END) STORED,
                ADD UNIQUE INDEX media_assignments_primary_context_unique (
                    entity_type,
                    entity_id,
                    role,
                    primary_locale_key,
                    primary_site_key,
                    primary_market_key
                )
                SQL);
        } else {
            throw new RuntimeException("Unsupported database driver for media assignment constraints: {$driver}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_assignments');
    }
};
