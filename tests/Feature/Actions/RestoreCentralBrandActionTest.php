<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\ArchiveCentralBrandAction;
use App\Actions\CentralCatalog\RestoreCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

class RestoreCentralBrandActionTest extends TestCase
{
    use DatabaseTruncation;

    public function test_restores_an_archived_brand_to_draft_for_explicit_review(): void
    {
        $brand = CentralBrand::factory()->archived()->create([
            'name' => 'Samsung',
            'country_code' => 'KR',
        ]);

        $result = app(RestoreCentralBrandAction::class)->handle(User::factory()->create(), $brand);

        $this->assertSame(CentralBrandStatus::Draft, $result->status);
        $this->assertSame('Samsung', $result->name);
        $this->assertSame('KR', $result->country_code);
    }

    #[DataProvider('nonArchivedStatusProvider')]
    public function test_restore_is_a_safe_no_op_for_non_archived_brands(CentralBrandStatus $status): void
    {
        $brand = CentralBrand::factory()->create(['status' => $status]);

        $result = app(RestoreCentralBrandAction::class)->handle(User::factory()->create(), $brand);

        $this->assertSame($status, $result->status);
    }

    /** @return iterable<string, array{CentralBrandStatus}> */
    public static function nonArchivedStatusProvider(): iterable
    {
        yield 'draft' => [CentralBrandStatus::Draft];
        yield 'active' => [CentralBrandStatus::Active];
    }

    public function test_restore_is_idempotent_after_an_archived_brand_returns_to_draft(): void
    {
        $brand = CentralBrand::factory()->archived()->create();

        app(RestoreCentralBrandAction::class)->handle(User::factory()->create(), $brand);
        $result = app(RestoreCentralBrandAction::class)->handle(User::factory()->create(), $brand->refresh());

        $this->assertSame(CentralBrandStatus::Draft, $result->status);
        $this->assertDatabaseCount('central_brands', 1);
    }

    public function test_concurrent_archive_and_restore_serialize_to_restored_draft(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated two-connection lifecycle test runs in the MariaDB and PostgreSQL matrix.');
        }

        $brand = CentralBrand::factory()->draft()->create();
        $actor = User::factory()->create();
        $coordinationDirectory = sys_get_temp_dir().'/cataloghub-brand-restore-'.bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($coordinationDirectory));

        $archiveRead = $coordinationDirectory.'/archive-read';
        $restoreRead = $coordinationDirectory.'/restore-read';
        $archiveCommitted = $coordinationDirectory.'/archive-committed';
        $outcome = $coordinationDirectory.'/outcome';
        $connectionName = 'brand_restore_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
        $parentPid = getmypid();
        $handledSelect = false;

        DB::listen(function (QueryExecuted $query) use (
            $archiveRead,
            $restoreRead,
            $archiveCommitted,
            $parentPid,
            &$handledSelect,
        ): void {
            if ($handledSelect || ! str_starts_with(ltrim(strtolower($query->sql)), 'select') || ! str_contains($query->sql, 'central_brands')) {
                return;
            }

            $handledSelect = true;

            if (getmypid() === $parentPid) {
                touch($archiveRead);
                $this->waitForFile($restoreRead, 0.4);

                return;
            }

            touch($restoreRead);
            $this->waitForFile($archiveCommitted, 5.0);
        });

        $childPid = pcntl_fork();
        $this->assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            $this->waitForFile($archiveRead, 5.0);

            try {
                $result = app(RestoreCentralBrandAction::class)->handle($actor, $brand);
                file_put_contents($outcome, $result->status->value);
            } catch (Throwable $exception) {
                file_put_contents($outcome, 'error:'.$exception::class);
            }

            exit(0);
        }

        try {
            app(ArchiveCentralBrandAction::class)->handle($actor, $brand);
        } finally {
            touch($archiveCommitted);
            pcntl_waitpid($childPid, $status);
        }

        $restoreOutcome = (string) file_get_contents($outcome);
        $finalStatus = $brand->fresh()->status;

        foreach ([$archiveRead, $restoreRead, $archiveCommitted, $outcome] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        rmdir($coordinationDirectory);

        $this->assertSame('draft', $restoreOutcome);
        $this->assertSame(CentralBrandStatus::Draft, $finalStatus);
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
