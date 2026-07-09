<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('central_categories', function (Blueprint $table): void {
            $table->string('status')->default('draft')->after('slug')->index();
        });
    }

    public function down(): void
    {
        Schema::table('central_categories', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};
