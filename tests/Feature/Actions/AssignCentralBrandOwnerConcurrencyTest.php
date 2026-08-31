<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\AssignCentralBrandOwnerAction;
use App\Enums\AuditAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

final class AssignCentralBrandOwnerConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_concurrent_assignments_serialize_on_the_brand_and_leave_one_deterministic_owner(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated ownership race runs in the MariaDB and PostgreSQL matrix.');
        }

        $actor = User::factory()->create();
        $brand = CentralBrand::factory()->create();
        $organizationA = Organization::factory()->create(['name' => 'First Owner']);
        $organizationB = Organization::factory()->create(['name' => 'Second Owner']);
        $directory = sys_get_temp_dir().'/cataloghub-brand-ownership-race-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $parentLocked = $directory.'/parent-locked';
        $childStarted = $directory.'/child-started';
        $childOutcome = $directory.'/child-outcome';
        $connectionName = 'brand_ownership_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
        $parentPid = getmypid();
        $handledBrandLock = false;

        DB::listen(function (QueryExecuted $query) use (
            $parentLocked,
            $childStarted,
            $parentPid,
            &$handledBrandLock,
        ): void {
            $sql = strtolower($query->sql);
            if ($handledBrandLock
                || getmypid() !== $parentPid
                || ! str_starts_with(ltrim($sql), 'select')
                || ! str_contains($sql, 'central_brands')
                || ! str_contains($sql, 'for update')) {
                return;
            }

            $handledBrandLock = true;
            touch($parentLocked);
            $this->waitForFile($childStarted, 5.0);
        });

        DB::disconnect($defaultConnection);
        $childPid = pcntl_fork();
        self::assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            $this->waitForFile($parentLocked, 5.0);
            touch($childStarted);

            try {
                app(AssignCentralBrandOwnerAction::class)->handle(
                    User::query()->findOrFail($actor->id),
                    CentralBrand::query()->findOrFail($brand->id),
                    Organization::query()->findOrFail($organizationB->id),
                );
                file_put_contents($childOutcome, 'assigned');
            } catch (Throwable $exception) {
                file_put_contents($childOutcome, 'error:'.$exception::class);
            }

            exit(0);
        }

        try {
            app(AssignCentralBrandOwnerAction::class)->handle($actor, $brand, $organizationA);
        } finally {
            pcntl_waitpid($childPid, $status);
        }

        self::assertTrue($handledBrandLock);
        self::assertSame('assigned', file_get_contents($childOutcome));
        self::assertSame(1, CentralBrandOwnership::query()->where('central_brand_id', $brand->id)->count());
        self::assertSame($organizationB->id, $brand->fresh()->ownership?->organization_id);
        self::assertSame(2, AuditLogEntry::query()
            ->where('action', AuditAction::CatalogBrandOwnerAssigned->value)
            ->where('subject_id', (string) $brand->id)
            ->count());

        foreach ([$parentLocked, $childStarted, $childOutcome] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        rmdir($directory);
    }

    private function waitForFile(string $path, float $timeoutSeconds): void
    {
        $deadline = microtime(true) + $timeoutSeconds;

        while (! file_exists($path) && microtime(true) < $deadline) {
            usleep(10_000);
        }

        self::assertFileExists($path, "Timed out waiting for concurrency marker: {$path}");
    }

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;

        parent::tearDown();
    }
}
