<?php

declare(strict_types=1);

namespace App\Services\ReferenceData;

use App\Data\ReferenceData\CountrySyncResult;
use App\Models\Geography\Country;
use App\Models\Geography\CountryTranslation;
use Illuminate\Support\Facades\DB;

final readonly class CountrySynchronizer
{
    public function __construct(private CountryDatasetLoader $loader) {}

    public function sync(
        ?string $directory = null,
        bool $dryRun = false,
        string $manifestFilename = 'manifest.json',
    ): CountrySyncResult {
        $dataset = $this->loader->load($directory, $manifestFilename);

        return DB::transaction(function () use ($dataset, $dryRun): CountrySyncResult {
            $created = $updated = $unchanged = $deactivated = 0;
            $translationsCreated = $translationsUpdated = $translationsUnchanged = 0;
            $sourceCodes = [];

            /** @var array<string, Country> $countriesByCode */
            $countriesByCode = Country::query()->get()->keyBy('alpha2')->all();

            foreach ($dataset['countries'] as $record) {
                $sourceCodes[] = $record['alpha2'];
                $attributes = [...$record, 'is_active' => true];
                $country = $countriesByCode[$record['alpha2']] ?? null;

                if ($country === null) {
                    $created++;
                    $country = new Country($attributes);
                    if (! $dryRun) {
                        $country->saveOrFail();
                        $countriesByCode[$record['alpha2']] = $country;
                    }
                } elseif ($country->only(array_keys($attributes)) !== $attributes) {
                    $updated++;
                    if (! $dryRun) {
                        $country->forceFill($attributes)->saveOrFail();
                    }
                } else {
                    $unchanged++;
                }
            }

            foreach ($countriesByCode as $code => $country) {
                if ($country->is_active && ! in_array($code, $sourceCodes, true)) {
                    $deactivated++;
                    if (! $dryRun) {
                        $country->forceFill(['is_active' => false])->saveOrFail();
                    }
                }
            }

            if ($dryRun && $created > 0) {
                $countriesByCode = Country::query()->get()->keyBy('alpha2')->all();
            }

            foreach ($dataset['translations'] as $record) {
                $country = $countriesByCode[$record['alpha2']] ?? null;

                if ($country === null) {
                    if ($dryRun) {
                        $translationsCreated++;

                        continue;
                    }

                    throw new \LogicException("Country {$record['alpha2']} was not persisted before its translations.");
                }

                $translation = CountryTranslation::query()
                    ->where('country_id', $country->getKey())
                    ->where('locale', $record['locale'])
                    ->first();

                if ($translation === null) {
                    $translationsCreated++;
                    if (! $dryRun) {
                        CountryTranslation::query()->create([
                            'country_id' => $country->getKey(),
                            'locale' => $record['locale'],
                            'name' => $record['name'],
                        ]);
                    }
                } elseif ($translation->name !== $record['name']) {
                    $translationsUpdated++;
                    if (! $dryRun) {
                        $translation->forceFill(['name' => $record['name']])->saveOrFail();
                    }
                } else {
                    $translationsUnchanged++;
                }
            }

            return new CountrySyncResult(
                $created,
                $updated,
                $unchanged,
                $deactivated,
                $translationsCreated,
                $translationsUpdated,
                $translationsUnchanged,
                $dryRun,
            );
        });
    }
}
