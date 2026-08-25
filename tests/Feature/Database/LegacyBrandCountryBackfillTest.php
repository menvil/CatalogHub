<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Services\ReferenceData\LegacyBrandCountryBackfill;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\Support\CountryReference;
use Tests\TestCase;

final class LegacyBrandCountryBackfillTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('legacy_brand_country_test', function (Blueprint $table): void {
            $table->id();
            $table->string('country_code', 20)->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
        });
    }

    public function test_lowercase_legacy_code_maps_to_country_fk_and_null_remains_null(): void
    {
        DB::table('legacy_brand_country_test')->insert([
            ['id' => 1, 'country_code' => ' kr ', 'country_id' => null],
            ['id' => 2, 'country_code' => null, 'country_id' => null],
        ]);

        app(LegacyBrandCountryBackfill::class)->run('legacy_brand_country_test');

        $this->assertSame(CountryReference::id('KR'), DB::table('legacy_brand_country_test')->where('id', 1)->value('country_id'));
        $this->assertNull(DB::table('legacy_brand_country_test')->where('id', 2)->value('country_id'));
    }

    public function test_unknown_legacy_code_fails_clearly_without_partial_backfill(): void
    {
        DB::table('legacy_brand_country_test')->insert([
            ['id' => 1, 'country_code' => 'kr', 'country_id' => null],
            ['id' => 2, 'country_code' => 'ZZ', 'country_id' => null],
        ]);

        try {
            app(LegacyBrandCountryBackfill::class)->run('legacy_brand_country_test');
            $this->fail('Unknown legacy country code was accepted.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('brand 2: ZZ', $exception->getMessage());
            $this->assertNull(DB::table('legacy_brand_country_test')->where('id', 1)->value('country_id'));
            $this->assertNull(DB::table('legacy_brand_country_test')->where('id', 2)->value('country_id'));
        }
    }
}
