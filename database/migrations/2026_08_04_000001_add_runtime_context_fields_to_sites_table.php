<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->char('currency_code', 3)->default('EUR')->after('default_locale');
            $table->string('timezone')->default('UTC')->after('currency_code');
            $table->index(['status', 'market_id'], 'sites_status_market_index');
        });

        DB::table('sites')
            ->join('markets', 'markets.id', '=', 'sites.market_id')
            ->select(['sites.id', 'markets.currency_code', 'markets.timezone'])
            ->orderBy('sites.id')
            ->each(function (object $site): void {
                DB::table('sites')->where('id', $site->id)->update([
                    'currency_code' => $site->currency_code,
                    'timezone' => $site->timezone,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table): void {
            $table->dropIndex('sites_status_market_index');
            $table->dropColumn(['currency_code', 'timezone']);
        });
    }
};
