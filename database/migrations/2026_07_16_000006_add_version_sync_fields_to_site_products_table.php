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
            ->whereNull('published_version')
            ->orWhere('published_version', '')
            ->update(['published_version' => 0]);

        Schema::table('site_products', function (Blueprint $table): void {
            $table->unsignedBigInteger('published_version')->default(0)->nullable(false)->change();
            $table->timestamp('last_synced_at')->nullable()->after('published_version');
            $table->string('sync_status')->nullable()->after('last_synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('site_products', function (Blueprint $table): void {
            $table->dropColumn(['last_synced_at', 'sync_status']);
            $table->string('published_version')->nullable()->default(null)->change();
        });
    }
};
