<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->boolean('in_stock')->default(false)->index()->after('offers_count');
        });
    }

    public function down(): void
    {
        Schema::table('site_search_documents', function (Blueprint $table): void {
            $table->dropColumn('in_stock');
        });
    }
};
