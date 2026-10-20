<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Two-process coordinated race for CA-018 composite saves (copied from the
 * Phase 19.1 hierarchy race): the parent holds its first write lock on
 * $lockedTable while the child's competing write must wait, then both commit
 * or reject on independent connections. Outcomes: success, validation:<keys>,
 * or error:<class>:<message>.
 */
trait CategoryFormRace
{
    /** @param callable(): mixed $parentAction
     * @param  callable(): mixed  $childAction
     * @param  callable(string): void  $verify
     */
    private function race(string $lockedTable, callable $parentAction, callable $childAction, callable $verify): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Two-process lock verification requires pcntl (available in the CI database matrix).');
        }
        $directory = sys_get_temp_dir().'/cataloghub-category-form-lock-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $originalConnection = DB::getDefaultConnection();
        $defaultConnection = $originalConnection;
        $sqlitePath = null;
        // Canonical SQLite runs on a real shared file for this test. In-memory
        // connections cannot prove exclusion between independent processes.
        if (DB::connection()->getDriverName() === 'sqlite') {
            $sqlitePath = $directory.'/database.sqlite';
            DB::statement("VACUUM INTO '".$sqlitePath."'");
            $defaultConnection = 'category_form_parent_file';
            config(['database.connections.'.$defaultConnection => [
                'driver' => 'sqlite', 'database' => $sqlitePath, 'foreign_key_constraints' => true,
                'busy_timeout' => 10000,
            ]]);
            DB::setDefaultConnection($defaultConnection);
        }
        $childConnection = 'category_form_child_connection';
        config(['database.connections.'.$childConnection => config('database.connections.'.$defaultConnection)]);
        $locked = $directory.'/locked';
        $started = $directory.'/child-lock-query-started';
        $lockTime = $directory.'/child-lock-query-time';
        $outcomeFile = $directory.'/outcome';
        $parentPid = getmypid();
        $handled = false;
        DB::listen(function (QueryExecuted $query) use ($lockedTable, $locked, $started, $lockTime, $parentPid, &$handled): void {
            if ($handled || getmypid() !== $parentPid || ! (str_starts_with(strtolower($query->sql), 'update') && str_contains($query->sql, $lockedTable))) {
                return;
            }
            $handled = true;
            touch($locked);
            if (! $this->waitForFile($started)) {
                throw new \RuntimeException('Child failed to start its concurrent action.');
            }
            // Hold the acquired lock while the child's actual UPDATE is in flight.
            // Its first lock query must not complete until this transaction commits.
            usleep(300000);
            if (file_exists($lockTime)) {
                throw new \RuntimeException('Child lock query completed while the parent still held the lock.');
            }
        });
        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);
        if ($pid === 0) {
            DB::setDefaultConnection($childConnection);
            if (! $this->waitForFile($locked)) {
                file_put_contents($outcomeFile, 'parent-timeout');
                exit(1);
            }
            $attempted = false;
            DB::connection($childConnection)->beforeExecuting(function (string $sql) use ($lockedTable, $started, &$attempted): void {
                if (! $attempted && (str_starts_with(strtolower($sql), 'update') && str_contains($sql, $lockedTable))) {
                    $attempted = true;
                    touch($started);
                }
            });
            $timed = false;
            DB::listen(function (QueryExecuted $query) use ($lockedTable, $lockTime, &$timed): void {
                if (! $timed && (str_starts_with(strtolower($query->sql), 'update') && str_contains($query->sql, $lockedTable))) {
                    $timed = true;
                    file_put_contents($lockTime, (string) $query->time);
                }
            });
            try {
                $childAction();
                file_put_contents($outcomeFile, 'success');
            } catch (ValidationException $e) {
                // Report every rejected key so tests prove the exact stale-state rule that fired.
                $keys = array_keys($e->errors());
                sort($keys);
                file_put_contents($outcomeFile, 'validation:'.implode(',', $keys));
            } catch (Throwable $e) {
                file_put_contents($outcomeFile, 'error:'.$e::class.':'.$e->getMessage());
            }
            exit(0);
        }
        try {
            $parentAction();
            pcntl_waitpid($pid, $status);
            self::assertTrue($handled);
            self::assertSame(0, pcntl_wexitstatus($status));
            self::assertFileExists($lockTime);
            self::assertGreaterThanOrEqual(100, (float) file_get_contents($lockTime), 'The child must actually wait on the held lock.');
            // Verify committed facts on a third connection, independent of
            // any PDO descriptor inherited by fork.
            DB::purge($defaultConnection);
            $verify((string) file_get_contents($outcomeFile));
        } finally {
            pcntl_waitpid($pid, $status);
            DB::disconnect($childConnection);
            if ($sqlitePath !== null) {
                DB::disconnect($defaultConnection);
            }
            DB::setDefaultConnection($originalConnection);
            foreach (glob($directory.'/*') ?: [] as $path) {
                unlink($path);
            }
            rmdir($directory);
        }
    }

    private function waitForFile(string $path): bool
    {
        $deadline = microtime(true) + 10;
        while (! file_exists($path) && microtime(true) < $deadline) {
            usleep(10000);
        }

        return file_exists($path);
    }
}
