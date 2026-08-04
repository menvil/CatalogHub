<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_products')
            ->select(['id', 'published_version'])
            ->orderBy('id')
            ->chunkById(500, function ($siteProducts): void {
                foreach ($siteProducts as $siteProduct) {
                    if (ctype_digit((string) $siteProduct->published_version)) {
                        continue;
                    }

                    DB::table('site_products')
                        ->where('id', $siteProduct->id)
                        ->update(['published_version' => 0]);
                }
            });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE "site_products"
                ALTER COLUMN "published_version" TYPE BIGINT
                USING CAST("published_version" AS BIGINT),
                ALTER COLUMN "published_version" SET DEFAULT 0,
                ALTER COLUMN "published_version" SET NOT NULL
                SQL);
        } else {
            Schema::table('site_products', function (Blueprint $table): void {
                $table->unsignedBigInteger('published_version')->default(0)->nullable(false)->change();
            });
        }

        Schema::table('site_products', function (Blueprint $table): void {
            $table->timestamp('last_synced_at')->nullable()->after('published_version');
            $table->string('sync_status')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('site_products', function (Blueprint $table): void {
            $table->dropColumn(['last_synced_at', 'sync_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                ALTER TABLE "site_products"
                ALTER COLUMN "published_version" DROP DEFAULT,
                ALTER COLUMN "published_version" DROP NOT NULL,
                ALTER COLUMN "published_version" TYPE VARCHAR(255)
                USING CAST("published_version" AS VARCHAR(255))
                SQL);
        } else {
            Schema::table('site_products', function (Blueprint $table): void {
                $table->string('published_version')->nullable()->default(null)->change();
            });
        }
    }
};
