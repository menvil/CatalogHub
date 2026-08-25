<?php

namespace Tests\Feature\Actions;

use App\Actions\CentralCatalog\ActivateCentralBrandAction;
use App\Actions\CentralCatalog\ArchiveCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

class ActivateCentralBrandActionTest extends TestCase
{
    use DatabaseTruncation;

    public function test_activates_a_draft_brand_without_changing_other_fields(): void
    {
        $brand = CentralBrand::factory()->draft()->create([
            'name' => 'Samsung',
            'website_url' => 'https://www.samsung.com',
        ]);

        $result = app(ActivateCentralBrandAction::class)->handle(User::factory()->create(), $brand);

        $this->assertSame(CentralBrandStatus::Active, $result->status);
        $this->assertSame('Samsung', $result->name);
        $this->assertSame('https://www.samsung.com', $result->website_url);
    }

    public function test_activation_is_idempotent_for_an_active_brand(): void
    {
        $brand = CentralBrand::factory()->active()->create();

        app(ActivateCentralBrandAction::class)->handle(User::factory()->create(), $brand);
        $result = app(ActivateCentralBrandAction::class)->handle(User::factory()->create(), $brand->refresh());

        $this->assertSame(CentralBrandStatus::Active, $result->status);
        $this->assertDatabaseCount('central_brands', 1);
    }

    public function test_rejects_direct_activation_of_an_archived_brand(): void
    {
        $brand = CentralBrand::factory()->archived()->create(['name' => 'Archived Brand']);

        try {
            app(ActivateCentralBrandAction::class)->handle(User::factory()->create(), $brand);
            $this->fail('An archived Brand was activated directly.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Archived brands must be restored before they can be activated.'],
                $exception->errors()['status'] ?? [],
            );
        }

        $brand->refresh();
        $this->assertSame(CentralBrandStatus::Archived, $brand->status);
        $this->assertSame('Archived Brand', $brand->name);
    }

    public function test_concurrent_archive_and_activation_never_finish_active_after_archive(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated two-connection lifecycle test runs in the MariaDB and PostgreSQL matrix.');
        }

        $brand = CentralBrand::factory()->draft()->create();
        $actor = User::factory()->create();
        $coordinationDirectory = sys_get_temp_dir().'/cataloghub-brand-lifecycle-'.bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($coordinationDirectory));

        $archiveRead = $coordinationDirectory.'/archive-read';
        $activationRead = $coordinationDirectory.'/activation-read';
        $archiveCommitted = $coordinationDirectory.'/archive-committed';
        $outcome = $coordinationDirectory.'/outcome';
        $connectionName = 'brand_lifecycle_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);
        $parentPid = getmypid();
        $handledSelect = false;

        DB::listen(function (QueryExecuted $query) use (
            $archiveRead,
            $activationRead,
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
                $this->waitForFile($activationRead, 0.4);

                return;
            }

            touch($activationRead);
            $this->waitForFile($archiveCommitted, 5.0);
        });

        $childPid = pcntl_fork();
        $this->assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            $this->waitForFile($archiveRead, 5.0);

            try {
                $result = app(ActivateCentralBrandAction::class)->handle($actor, $brand);
                file_put_contents($outcome, $result->status->value);
            } catch (ValidationException) {
                file_put_contents($outcome, 'rejected');
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

        $this->assertContains((string) file_get_contents($outcome), ['archived', 'rejected']);
        $this->assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);

        foreach ([$archiveRead, $activationRead, $archiveCommitted, $outcome] as $path) {
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
