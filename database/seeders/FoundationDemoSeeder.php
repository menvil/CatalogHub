<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use LogicException;

final class FoundationDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new LogicException('Foundation demo data may only be seeded in local or testing environments.');
        }

        $this->call([
            FoundationRolesSeeder::class,
            MeasurementDimensionsSeeder::class,
            MetricMeasurementUnitsSeeder::class,
            ImperialMeasurementUnitsSeeder::class,
            BlockRegistrySeeder::class,
            SiteFoundationSeeder::class,
            FoundationDemoUsersSeeder::class,
        ]);
    }
}
