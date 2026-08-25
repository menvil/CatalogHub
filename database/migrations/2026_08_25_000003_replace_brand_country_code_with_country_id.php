<?php

use App\Services\ReferenceData\LegacyBrandCountryBackfill;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $backfill = app(LegacyBrandCountryBackfill::class);
        $backfill->validate();

        if (! Schema::hasColumn('central_brands', 'country_id')) {
            Schema::table('central_brands', function (Blueprint $table): void {
                $table->foreignId('country_id')->nullable()->after('website_url')->constrained('countries')->restrictOnDelete();
            });
        }

        $backfill->run();

        if (Schema::hasColumn('central_brands', 'country_code')) {
            Schema::table('central_brands', function (Blueprint $table): void {
                $table->dropColumn('country_code');
            });
        }
    }

    public function down(): void
    {
        Schema::table('central_brands', function (Blueprint $table): void {
            $table->string('country_code', 2)->nullable()->after('website_url');
        });

        DB::table('central_brands')
            ->whereNotNull('country_id')
            ->orderBy('id')
            ->chunkById(200, function ($brands): void {
                $countryCodes = DB::table('countries')
                    ->whereIn('id', $brands->pluck('country_id')->all())
                    ->pluck('alpha2', 'id');

                foreach ($brands as $brand) {
                    DB::table('central_brands')->where('id', $brand->id)->update([
                        'country_code' => $countryCodes->get($brand->country_id),
                    ]);
                }
            });

        Schema::table('central_brands', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('country_id');
        });
    }
};
