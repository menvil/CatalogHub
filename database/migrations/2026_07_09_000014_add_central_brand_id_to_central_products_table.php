<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_products', function (Blueprint $table): void {
            $table->foreignId('central_brand_id')
                ->nullable()
                ->after('id')
                ->constrained('central_brands')
                ->nullOnDelete()
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('central_products', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('central_brand_id');
        });
    }
};
