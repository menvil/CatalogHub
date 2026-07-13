<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('normalized_product_drafts', function (Blueprint $table): void {
            $table->foreignId('published_central_product_id')
                ->nullable()
                ->after('matched_central_product_id')
                ->constrained('central_products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('normalized_product_drafts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('published_central_product_id');
        });
    }
};
