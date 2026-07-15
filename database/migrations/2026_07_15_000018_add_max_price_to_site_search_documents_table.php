<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->decimal('max_price', 12, 2)->nullable()->after('min_price');
            $table->index(
                ['site_id', 'min_price', 'max_price'],
                'site_search_documents_site_price_range_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->dropIndex('site_search_documents_site_price_range_index');
            $table->dropColumn('max_price');
        });
    }
};
