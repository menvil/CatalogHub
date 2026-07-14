<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Demo\PublicDemoSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MeasurementDimensionsSeeder::class,
            MetricMeasurementUnitsSeeder::class,
            ImperialMeasurementUnitsSeeder::class,
            BlockRegistrySeeder::class,
            PublicDemoSeeder::class,
        ]);

        User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );
    }
}
