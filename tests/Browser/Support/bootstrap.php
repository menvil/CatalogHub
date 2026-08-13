<?php

declare(strict_types=1);

use Database\Seeders\FoundationDemoSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\BrandFormFixture;
use Tests\Support\BrandListFixture;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (Artisan::call('migrate:fresh', [
    '--force' => true,
    '--no-interaction' => true,
    '--seeder' => FoundationDemoSeeder::class,
]) !== 0) {
    fwrite(STDERR, Artisan::output());
    exit(1);
}

BrandListFixture::create();
BrandFormFixture::create();
