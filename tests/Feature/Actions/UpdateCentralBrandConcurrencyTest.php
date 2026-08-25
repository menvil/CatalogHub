<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\UpdateCentralBrandAction;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\Support\CountryReference;
use Tests\TestCase;
use Throwable;

final class UpdateCentralBrandConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_concurrent_disjoint_updates_normalize_against_the_locked_brand_state(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated stale-snapshot test runs in the MariaDB and PostgreSQL matrix.');
        }

        $brand = CentralBrand::factory()->create([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'website_url' => 'https://old.example',
            'country_id' => CountryReference::id('KR'),
        ]);
        $actor = User::factory()->create();
        $coordinationDirectory = sys_get_temp_dir().'/cataloghub-brand-update-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($coordinationDirectory));

        $parentSelected = $coordinationDirectory.'/parent-selected';
        $childStarted = $coordinationDirectory.'/child-started';
        $childSelected = $coordinationDirectory.'/child-selected';
        $parentCommitted = $coordinationDirectory.'/parent-committed';
        $childOutcome = $coordinationDirectory.'/child-outcome';
        $connectionName = 'brand_update_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
        $parentPid = getmypid();
        $handledSelect = false;

        DB::listen(function (QueryExecuted $query) use (
            $parentSelected,
            $childStarted,
            $childSelected,
            $parentCommitted,
            $parentPid,
            &$handledSelect,
        ): void {
            $sql = strtolower($query->sql);
            if ($handledSelect || ! str_starts_with(ltrim($sql), 'select') || ! str_contains($sql, 'central_brands')) {
                return;
            }

            $handledSelect = true;
            $usesRowLock = str_contains($sql, 'for update');

            if (getmypid() === $parentPid) {
                touch($parentSelected);
                $this->waitForFile($usesRowLock ? $childStarted : $childSelected, 5.0);

                return;
            }

            touch($childSelected);
            if (! $usesRowLock) {
                $this->waitForFile($parentCommitted, 5.0);
            }
        });

        $childPid = pcntl_fork();
        self::assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            $this->waitForFile($parentSelected, 5.0);
            touch($childStarted);

            try {
                app(UpdateCentralBrandAction::class)->handle($actor, $brand, new CentralBrandInput(
                    name: 'Samsung',
                    hasCountryId: true,
                    countryId: CountryReference::id('DE'),
                ));
                file_put_contents($childOutcome, 'updated');
            } catch (Throwable $exception) {
                file_put_contents($childOutcome, 'error:'.$exception::class);
            }

            exit(0);
        }

        try {
            app(UpdateCentralBrandAction::class)->handle($actor, $brand, new CentralBrandInput(
                name: 'Samsung',
                hasWebsiteUrl: true,
                websiteUrl: 'https://new.example',
            ));
        } finally {
            touch($parentCommitted);
            pcntl_waitpid($childPid, $status);
        }

        self::assertSame('updated', file_get_contents($childOutcome));
        $brand->refresh();
        self::assertSame('https://new.example', $brand->website_url);
        self::assertSame('DE', $brand->country()->first()?->alpha2);

        $brand->delete();

        foreach ([$parentSelected, $childStarted, $childSelected, $parentCommitted, $childOutcome] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        rmdir($coordinationDirectory);
    }

    private function waitForFile(string $path, float $timeoutSeconds): bool
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (! file_exists($path) && microtime(true) < $deadline) {
            usleep(10_000);
        }

        return file_exists($path);
    }

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }
}
