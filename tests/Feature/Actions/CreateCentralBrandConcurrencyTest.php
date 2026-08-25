<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\CreateCentralBrandAction;
use App\Data\CentralCatalog\CentralBrandInput;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

final class CreateCentralBrandConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_concurrent_unicode_equivalent_names_create_one_brand_and_return_a_name_error(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated two-connection duplicate-name test runs in the MariaDB and PostgreSQL matrix.');
        }

        $actor = User::factory()->create();
        $coordinationDirectory = sys_get_temp_dir().'/cataloghub-brand-name-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($coordinationDirectory));

        $parentValidated = $coordinationDirectory.'/parent-validated';
        $childValidated = $coordinationDirectory.'/child-validated';
        $parentCommitted = $coordinationDirectory.'/parent-committed';
        $outcome = $coordinationDirectory.'/outcome';
        $connectionName = 'brand_name_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
        $parentPid = getmypid();
        $handledIdentityQuery = false;

        DB::listen(function (QueryExecuted $query) use (
            $parentValidated,
            $childValidated,
            $parentCommitted,
            $parentPid,
            &$handledIdentityQuery,
        ): void {
            if ($handledIdentityQuery || ! str_contains($query->sql, 'normalized_name_hash')) {
                return;
            }

            $handledIdentityQuery = true;

            if (getmypid() === $parentPid) {
                touch($parentValidated);
                $this->waitForFile($childValidated, 5.0);

                return;
            }

            touch($childValidated);
            $this->waitForFile($parentCommitted, 5.0);
        });

        $childPid = pcntl_fork();
        self::assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            $this->waitForFile($parentValidated, 5.0);

            try {
                app(CreateCentralBrandAction::class)->handle($actor, new CentralBrandInput(
                    name: 'électro',
                    slug: 'electro-child',
                ));
                file_put_contents($outcome, 'created');
            } catch (ValidationException $exception) {
                file_put_contents($outcome, isset($exception->errors()['name']) ? 'name-error' : 'other-validation-error');
            } catch (Throwable $exception) {
                file_put_contents($outcome, 'error:'.$exception::class);
            }

            exit(0);
        }

        try {
            app(CreateCentralBrandAction::class)->handle($actor, new CentralBrandInput(
                name: 'ÉLECTRO',
                slug: 'electro-parent',
            ));
        } finally {
            touch($parentCommitted);
            pcntl_waitpid($childPid, $status);
        }

        self::assertSame('name-error', file_get_contents($outcome));
        self::assertSame(1, CentralBrand::query()->count());
        self::assertSame('ÉLECTRO', CentralBrand::query()->sole()->name);

        CentralBrand::query()->delete();

        foreach ([$parentValidated, $childValidated, $parentCommitted, $outcome] as $path) {
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
