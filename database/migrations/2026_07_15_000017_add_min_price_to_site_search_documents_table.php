<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->decimal('min_price', 12, 2)->nullable()->index()->after('search_text');
        });
    }

    public function down(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->dropColumn('min_price');
        });
    }
};
