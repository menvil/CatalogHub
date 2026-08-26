<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\ResolveCatalogTagAction;
use App\Models\CentralCatalog\CatalogTag;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

final class ResolveCatalogTagConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_concurrent_case_variants_resolve_to_one_catalog_tag(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated Catalog Tag unique-race test runs in the MariaDB and PostgreSQL matrix.');
        }

        $coordinationDirectory = sys_get_temp_dir().'/cataloghub-tag-race-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($coordinationDirectory));
        $parentSelected = $coordinationDirectory.'/parent-selected';
        $childSelected = $coordinationDirectory.'/child-selected';
        $outcome = $coordinationDirectory.'/outcome';
        $connectionName = 'catalog_tag_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
        $parentPid = getmypid();
        $handledSelect = false;

        DB::listen(function (QueryExecuted $query) use ($parentSelected, $childSelected, $parentPid, &$handledSelect): void {
            $sql = strtolower($query->sql);
            if ($handledSelect || ! str_starts_with(ltrim($sql), 'select') || ! str_contains($sql, 'catalog_tags')) {
                return;
            }

            $handledSelect = true;
            if (getmypid() === $parentPid) {
                touch($parentSelected);
                $this->waitForFile($childSelected, 5.0);

                return;
            }

            touch($childSelected);
        });

        // Never fork a live PDO socket: the child's connection destructor can send
        // COM_QUIT over the inherited MariaDB descriptor and invalidate the parent.
        DB::disconnect($defaultConnection);
        $childPid = pcntl_fork();
        self::assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            $this->waitForFile($parentSelected, 5.0);

            try {
                $tag = app(ResolveCatalogTagAction::class)->handle('premium');
                file_put_contents($outcome, 'resolved:'.$tag->getKey());
            } catch (Throwable $exception) {
                file_put_contents($outcome, 'error:'.$exception::class);
            }

            exit(0);
        }

        try {
            $parentTag = app(ResolveCatalogTagAction::class)->handle('Premium');
        } finally {
            pcntl_waitpid($childPid, $status);
        }

        self::assertSame('resolved:'.$parentTag->getKey(), file_get_contents($outcome));
        self::assertSame(1, CatalogTag::query()->count());
        self::assertContains(CatalogTag::query()->sole()->name, ['Premium', 'premium']);

        foreach ([$parentSelected, $childSelected, $outcome] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }
        rmdir($coordinationDirectory);
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
}
