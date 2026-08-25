<?php

declare(strict_types=1);

namespace App\Services\ReferenceData;

use App\Models\Geography\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class LegacyBrandCountryBackfill
{
    public function run(string $table = 'central_brands'): void
    {
        $countryIds = Country::query()->pluck('id', 'alpha2');
        $legacyRows = $this->legacyRows($table);
        $this->assertAllCodesMapped($legacyRows, $countryIds);

        DB::transaction(function () use ($countryIds, $legacyRows): void {
            $legacyRows->newQuery()
                ->whereNotNull('country_code')
                ->orderBy('id')
                ->chunkById(200, function ($brands) use ($countryIds, $legacyRows): void {
                    foreach ($brands as $brand) {
                        $code = strtoupper(trim((string) $brand->getAttribute('country_code')));
                        $legacyRows->newQuery()->whereKey($brand->getKey())->update([
                            'country_id' => $countryIds->get($code),
                        ]);
                    }
                });

            $unmappedCount = $legacyRows->newQuery()
                ->whereNotNull('country_code')
                ->whereNull('country_id')
                ->count();

            if ($unmappedCount !== 0) {
                throw new RuntimeException("Country backfill verification failed for {$unmappedCount} central Brand rows.");
            }
        });
    }

    public function validate(string $table = 'central_brands'): void
    {
        $this->assertAllCodesMapped(
            $this->legacyRows($table),
            Country::query()->pluck('id', 'alpha2'),
        );
    }

    private function assertAllCodesMapped(Model $legacyRows, $countryIds): void
    {
        $unmapped = [];

        $legacyRows->newQuery()
            ->whereNotNull('country_code')
            ->orderBy('id')
            ->chunkById(200, function ($brands) use ($countryIds, &$unmapped): void {
                foreach ($brands as $brand) {
                    $code = strtoupper(trim((string) $brand->getAttribute('country_code')));

                    if (preg_match('/\A[A-Z]{2}\z/', $code) !== 1 || ! $countryIds->has($code)) {
                        $unmapped[] = "brand {$brand->getKey()}: ".($code === '' ? '[blank]' : $code);
                    }
                }
            });

        if ($unmapped !== []) {
            throw new RuntimeException(
                'Cannot migrate central_brands.country_code; unmapped legacy values: '.implode(', ', array_slice($unmapped, 0, 20)),
            );
        }
    }

    private function legacyRows(string $table): Model
    {
        $model = new class extends Model
        {
            public $timestamps = false;

            protected $guarded = [];
        };
        $model->setTable($table);

        return $model;
    }
}
