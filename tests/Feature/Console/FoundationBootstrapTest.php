<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Database\Seeders\FoundationDemoSeeder;
use Database\Seeders\FoundationDemoUsersSeeder;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class FoundationBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_foundation_demo_seed_is_complete_idempotent_and_contains_no_business_data(): void
    {
        $this->seed(FoundationDemoSeeder::class);
        $this->seed(FoundationDemoSeeder::class);

        self::assertSame(count(SiteFoundationSeeder::SITE_CODES), Site::query()->whereIn('code', SiteFoundationSeeder::SITE_CODES)->count());
        self::assertSame(count(FoundationDemoUsersSeeder::PERSONAS), User::query()->whereIn(
            'email',
            array_column(FoundationDemoUsersSeeder::PERSONAS, 'email'),
        )->count());
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

    public function test_documented_bootstrap_runs_the_expected_local_workflow(): void
    {
        $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);
        [$root, $log] = $this->bootstrapHarness();

        self::assertSame('bash scripts/bootstrap-foundation.sh', $composer['scripts']['bootstrap:foundation']);

        try {
            $process = $this->runBootstrap($root, $log, ['APP_ENV' => 'local']);

            self::assertTrue($process->isSuccessful(), $process->getErrorOutput());
            self::assertStringContainsString('Foundation install verified: 3 sites, 8 users, 6 memberships, 0 catalog records.', $process->getOutput());
            self::assertStringContainsString('Foundation bootstrap completed successfully.', $process->getOutput());
            self::assertFileExists($root.'/.env');
            self::assertSame([
                'php artisan env --no-ansi',
                'php artisan key:generate --force --no-interaction',
                'php artisan migrate:fresh --force --no-interaction --seeder=Database\Seeders\FoundationDemoSeeder',
                'php artisan storage:link --force --no-interaction',
                'npm ci',
                'npm run build',
                'php artisan foundation:verify --no-interaction',
                'php artisan route:list --path=admin --except-vendor --no-ansi',
            ], file($log, FILE_IGNORE_NEW_LINES));
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_bootstrap_rejects_explicit_production_without_writing_env(): void
    {
        [$root, $log] = $this->bootstrapHarness();

        try {
            $process = $this->runBootstrap($root, $log, ['APP_ENV' => 'production']);

            self::assertFalse($process->isSuccessful());
            self::assertStringContainsString('refuses to run', $process->getErrorOutput());
            self::assertFileDoesNotExist($root.'/.env');
            self::assertFileDoesNotExist($log);
        } finally {
            File::deleteDirectory($root);
        }
    }

    public function test_bootstrap_fails_when_no_admin_routes_exist(): void
    {
        [$root, $log] = $this->bootstrapHarness();

        try {
            $process = $this->runBootstrap($root, $log, [
                'APP_ENV' => 'local',
                'BOOTSTRAP_EMPTY_ROUTES' => '1',
            ]);

            self::assertFalse($process->isSuccessful());
            self::assertStringContainsString('could not find admin routes', $process->getErrorOutput());
        } finally {
            File::deleteDirectory($root);
        }
    }

    /** @return array{string, string} */
    private function bootstrapHarness(): array
    {
        $root = sys_get_temp_dir().'/cataloghub-bootstrap-'.bin2hex(random_bytes(8));
        $bin = $root.'/bin';
        $log = $root.'/bootstrap.log';
        File::ensureDirectoryExists($root.'/scripts');
        File::ensureDirectoryExists($bin);
        File::copy(base_path('scripts/bootstrap-foundation.sh'), $root.'/scripts/bootstrap-foundation.sh');
        File::copy(base_path('.env.example'), $root.'/.env.example');
        File::put($bin.'/php', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
printf 'php %s\n' "$*" >> "$BOOTSTRAP_LOG"

case "$*" in
    'artisan env --no-ansi') printf '%s\n' 'Environment [local]' ;;
    *'artisan foundation:verify'*) printf '%s\n' 'Foundation install verified: 3 sites, 8 users, 6 memberships, 0 catalog records.' ;;
    *'artisan route:list'*)
        if [[ "${BOOTSTRAP_EMPTY_ROUTES:-0}" == '1' ]]; then
            printf "%s\n" "Your application doesn't have any routes matching the given criteria."
        else
            printf '%s\n' 'GET|HEAD admin/central'
        fi
        ;;
esac
BASH);
        File::put($bin.'/npm', <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail
printf 'npm %s\n' "$*" >> "$BOOTSTRAP_LOG"
BASH);
        chmod($bin.'/php', 0755);
        chmod($bin.'/npm', 0755);

        return [$root, $log];
    }

    /** @param array<string, string> $environment */
    private function runBootstrap(string $root, string $log, array $environment): Process
    {
        $process = new Process(['bash', 'scripts/bootstrap-foundation.sh'], $root, [
            'APP_KEY' => '',
            ...$environment,
            'BOOTSTRAP_LOG' => $log,
            'PATH' => $root.'/bin:'.(string) getenv('PATH'),
        ]);
        $process->run();

        return $process;
    }
}
