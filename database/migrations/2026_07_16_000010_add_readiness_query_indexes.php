<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table): void {
            $table->index(
                ['import_source_id', 'status', 'created_at'],
                'import_batches_source_status_created_idx',
            );
        });

        Schema::table('central_change_requests', function (Blueprint $table): void {
            $table->index(
                ['status', 'created_at'],
                'change_requests_status_created_idx',
            );
        });

        Schema::table('site_products', function (Blueprint $table): void {
            $table->index(
                ['site_id', 'sync_status'],
                'site_products_site_sync_status_idx',
            );
        });

        Schema::table('market_offers', function (Blueprint $table): void {
            $table->index(
                ['market_id', 'central_product_id', 'status', 'currency'],
                'market_offers_site_product_status_idx',
            );
        });

        Schema::table('price_source_sync_logs', function (Blueprint $table): void {
            $table->index(
                ['price_source_id', 'status', 'finished_at'],
                'price_sync_logs_source_status_finished_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('price_source_sync_logs', function (Blueprint $table): void {
            $table->dropIndex('price_sync_logs_source_status_finished_idx');
        });

        Schema::table('market_offers', function (Blueprint $table): void {
            $table->dropIndex('market_offers_site_product_status_idx');
        });

        Schema::table('site_products', function (Blueprint $table): void {
            $table->dropIndex('site_products_site_sync_status_idx');
        });

        Schema::table('central_change_requests', function (Blueprint $table): void {
            $table->dropIndex('change_requests_status_created_idx');
        });

        Schema::table('import_batches', function (Blueprint $table): void {
            // MariaDB may choose the composite readiness index to enforce this foreign key.
            // Keep a temporary single-column index until the older table migration drops it.
            $table->index('import_source_id', 'import_batches_source_rollback_idx');
            $table->dropIndex('import_batches_source_status_created_idx');
        });
    }
};
