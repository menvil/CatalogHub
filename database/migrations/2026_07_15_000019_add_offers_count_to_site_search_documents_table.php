<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->unsignedInteger('offers_count')->default(0)->after('max_price');
        });
    }

    public function down(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->dropColumn('offers_count');
        });
    }
};
