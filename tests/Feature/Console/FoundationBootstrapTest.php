<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Database\Seeders\FoundationDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class FoundationBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_demo_seed_is_complete_idempotent_and_contains_no_business_data(): void
    {
        $this->seed(FoundationDemoSeeder::class);
        $this->seed(FoundationDemoSeeder::class);

        self::assertSame(3, Site::query()->whereIn('code', [
            'tech-germany',
            'monitors-germany',
            'archived-germany',
        ])->count());
        self::assertSame(8, User::query()->where('email', 'like', '%@demo.cataloghub.test')->count());
        self::assertSame(6, SiteMembership::query()->count());
        self::assertSame(0, CentralBrand::query()->count());
        self::assertSame(0, CentralProduct::query()->count());

        $this->artisan('foundation:verify')->assertExitCode(0);
    }

    public function test_verification_fails_when_the_fixture_graph_is_incomplete(): void
    {
        $this->seed(FoundationDemoSeeder::class);
        User::query()->where('email', 'site-admin@demo.cataloghub.test')->delete();

        $this->artisan('foundation:verify')
            ->expectsOutputToContain('Expected 8 foundation demo users')
            ->assertExitCode(1);
    }

    public function test_documented_one_command_bootstrap_is_fail_fast_and_production_guarded(): void
    {
        $root = base_path();
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $script = (string) file_get_contents($root.'/scripts/bootstrap-foundation.sh');

        self::assertSame('bash scripts/bootstrap-foundation.sh', $composer['scripts']['bootstrap:foundation']);
        self::assertStringContainsString('set -euo pipefail', $script);
        self::assertStringContainsString('migrate:fresh', $script);
        self::assertStringContainsString('FoundationDemoSeeder', $script);
        self::assertStringContainsString('npm ci', $script);
        self::assertStringContainsString('npm run build', $script);
        self::assertStringContainsString('foundation:verify', $script);

        $database = tempnam(sys_get_temp_dir(), 'cataloghub-production-guard-');
        self::assertIsString($database);
        file_put_contents($database, 'untouched');
        $process = new Process(['bash', 'scripts/bootstrap-foundation.sh'], $root, [
            'APP_ENV' => 'production',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $database,
            'DB_URL' => '',
        ]);
        $process->run();

        try {
            self::assertFalse($process->isSuccessful());
            self::assertStringContainsString('refuses to run', $process->getErrorOutput());
            self::assertSame('untouched', file_get_contents($database));
        } finally {
            unlink($database);
        }
    }
}
