<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\FoundationVisualFixture;

require dirname(__DIR__, 3).'/vendor/autoload.php';

$app = require dirname(__DIR__, 3).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (Artisan::call('migrate:fresh', ['--force' => true, '--no-interaction' => true]) !== 0) {
    fwrite(STDERR, Artisan::output());
    exit(1);
}

User::factory()->centralAdmin()->create([
    ...FoundationVisualFixture::centralAdmin(),
    'password' => 'password',
]);
