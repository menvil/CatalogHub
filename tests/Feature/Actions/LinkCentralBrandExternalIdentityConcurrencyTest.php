<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Imports\LinkCentralBrandExternalIdentityAction;
use App\Enums\AuditAction;
use App\Exceptions\Imports\ExternalIdentityConflictException;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

final class LinkCentralBrandExternalIdentityConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_concurrent_links_to_different_brands_have_one_winner_and_one_controlled_conflict(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated external identity race runs in the MariaDB and PostgreSQL matrix.');
        }

        $actor = User::factory()->create();
        $brandA = CentralBrand::factory()->create();
        $brandB = CentralBrand::factory()->create();
        $source = ImportSource::factory()->create();
        $directory = sys_get_temp_dir().'/cataloghub-brand-identity-race-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $childReady = $directory.'/child-ready';
        $start = $directory.'/start';
        $childOutcome = $directory.'/child-outcome';
        $connectionName = 'brand_external_identity_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
        DB::disconnect($defaultConnection);
        $childPid = pcntl_fork();
        self::assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            touch($childReady);
            $this->waitForFile($start, 5.0);

            try {
                app(LinkCentralBrandExternalIdentityAction::class)->handle(
                    User::query()->findOrFail($actor->id),
                    CentralBrand::query()->findOrFail($brandB->id),
                    ImportSource::query()->findOrFail($source->id),
                    'brand-00142',
                    null,
                );
                file_put_contents($childOutcome, 'linked');
            } catch (ExternalIdentityConflictException) {
                file_put_contents($childOutcome, 'conflict');
            } catch (Throwable $exception) {
                file_put_contents($childOutcome, 'error:'.$exception::class);
            }

            exit(0);
        }

        try {
            $this->waitForFile($childReady, 5.0);
            touch($start);

            try {
                app(LinkCentralBrandExternalIdentityAction::class)->handle(
                    User::query()->findOrFail($actor->id),
                    CentralBrand::query()->findOrFail($brandA->id),
                    ImportSource::query()->findOrFail($source->id),
                    'brand-00142',
                    null,
                );
                $parentOutcome = 'linked';
            } catch (ExternalIdentityConflictException) {
                $parentOutcome = 'conflict';
            }
        } finally {
            pcntl_waitpid($childPid, $status);
        }

        $outcomes = [$parentOutcome, (string) file_get_contents($childOutcome)];
        sort($outcomes);
        self::assertSame(['conflict', 'linked'], $outcomes);
        self::assertSame(1, CentralBrandExternalIdentity::query()->count());
        self::assertSame(1, AuditLogEntry::query()->where('action', AuditAction::CatalogBrandExternalIdentityLinked->value)->count());

        foreach ([$childReady, $start, $childOutcome] as $path) {
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
}
