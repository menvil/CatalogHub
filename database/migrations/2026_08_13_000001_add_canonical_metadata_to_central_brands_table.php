<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_brands', function (Blueprint $table): void {
            $table->string('website_url')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('central_brands', function (Blueprint $table): void {
            $table->dropIndex(['name']);
            $table->dropColumn(['website_url', 'country_code']);
        });
    }
};
