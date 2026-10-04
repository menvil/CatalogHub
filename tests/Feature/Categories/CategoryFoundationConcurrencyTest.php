<?php

namespace Tests\Feature\Categories;

use App\Actions\CategorySchema\CreateAttributeSectionAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Actions\CentralCatalog\ReorderCentralCategoriesAction;
use App\Actions\CentralCatalog\ReparentCentralCategoryAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use App\Services\Categories\CategoryHierarchy;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;
use Throwable;

final class CategoryFoundationConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    public function test_two_root_reorders_serialize_and_the_second_rejects_the_stale_revision(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $a = CentralCategory::factory()->create(['position' => 0]);
        $b = CentralCategory::factory()->create(['position' => 1]);
        $this->race('category_hierarchy_scopes',
            fn () => app(ReorderCentralCategoriesAction::class)->handle($actor, null, [$b->id, $a->id], 0),
            fn () => app(ReorderCentralCategoriesAction::class)->handle($actor, null, [$a->id, $b->id], 0),
            function (string $outcome) use ($a, $b): void {
                self::assertSame('stale', $outcome);
                self::assertSame([$b->id, $a->id], CentralCategory::query()->orderBy('position')->pluck('id')->all());
                self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.category.reordered')->count());
            });
    }

    public function test_scope_creation_is_serialized_and_two_child_creates_cannot_share_one_revision(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $parent = CentralCategory::factory()->create();
        $this->race('category_hierarchy_scopes',
            fn () => app(CreateCentralCategoryAction::class)->handle($actor, ['name' => 'One', 'slug' => 'one', 'parent_id' => $parent->id], 0),
            fn () => app(CreateCentralCategoryAction::class)->handle($actor, ['name' => 'Two', 'slug' => 'two', 'parent_id' => $parent->id], 0),
            function (string $outcome) use ($parent): void {
                self::assertSame('stale', $outcome);
                self::assertSame(1, CentralCategory::query()->where('parent_id', $parent->id)->count());
                self::assertSame(1, app(CategoryHierarchy::class)->revision($parent->id));
                self::assertSame(1, CategoryHierarchyScope::query()->whereKey('parent:'.$parent->id)->count());
            });
    }

    public function test_opposite_reparents_cannot_create_a_cycle_across_disjoint_parent_scopes(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $a = CentralCategory::factory()->create(['position' => 0]);
        $b = CentralCategory::factory()->create(['position' => 1]);
        $this->race('category_hierarchy_scopes',
            fn () => app(ReparentCentralCategoryAction::class)->handle($actor, $a, $b->id, 0, 0),
            fn () => app(ReparentCentralCategoryAction::class)->handle($actor, $b, $a->id, 0, 0),
            function (string $outcome) use ($a, $b, $actor): void {
                self::assertSame('stale', $outcome);
                self::assertSame($b->id, CentralCategory::query()->findOrFail($a->id)->parent_id);
                self::assertNull(CentralCategory::query()->findOrFail($b->id)->parent_id);
                try {
                    app(ReparentCentralCategoryAction::class)->handle($actor, $b, $a->id, 1, 0);
                    self::fail('Cycle created after retry');
                } catch (ValidationException) {
                    self::assertSame(1, AuditLogEntry::query()->count());
                }
            });
    }

    public function test_schema_mutation_waits_for_review_then_invalidates_that_review(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $this->race('central_categories',
            fn () => app(MarkCategorySchemaReviewedAction::class)->handle($category, 1, $actor),
            fn () => app(CreateAttributeSectionAction::class)->handle($category, ['name' => 'Specs', 'code' => 'specs'], $actor),
            function (string $outcome) use ($category): void {
                self::assertSame('success', $outcome);
                $current = CentralCategory::query()->findOrFail($category->id);
                self::assertSame('draft', $current->schema_status->value);
                self::assertSame(2, $current->schema_revision);
                self::assertNull($current->schema_reviewed_revision);
                self::assertSame(1, $current->attributeSections()->count());
                self::assertSame(['catalog.category.schema.reviewed', 'catalog.category.schema.invalidated'], AuditLogEntry::query()->orderBy('id')->pluck('action')->all());
            });
    }

    /** @param callable(): mixed $parentAction
     * @param  callable(): mixed  $childAction
     * @param  callable(string): void  $verify
     */
    private function race(string $lockedTable, callable $parentAction, callable $childAction, callable $verify): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Two-process lock verification requires pcntl (available in the CI database matrix).');
        }
        $directory = sys_get_temp_dir().'/cataloghub-category-lock-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $originalConnection = DB::getDefaultConnection();
        $defaultConnection = $originalConnection;
        $sqlitePath = null;
        // Canonical SQLite runs on a real shared file for this test. In-memory
        // connections cannot prove exclusion between independent processes.
        if (DB::connection()->getDriverName() === 'sqlite') {
            $sqlitePath = $directory.'/database.sqlite';
            DB::statement("VACUUM INTO '".$sqlitePath."'");
            $defaultConnection = 'category_parent_file';
            config(['database.connections.'.$defaultConnection => [
                'driver' => 'sqlite', 'database' => $sqlitePath, 'foreign_key_constraints' => true,
                'busy_timeout' => 10000,
            ]]);
            DB::setDefaultConnection($defaultConnection);
        }
        $childConnection = 'category_child_connection';
        config(['database.connections.'.$childConnection => config('database.connections.'.$defaultConnection)]);
        $locked = $directory.'/locked';
        $started = $directory.'/child-started';
        $outcomeFile = $directory.'/outcome';
        $parentPid = getmypid();
        $handled = false;
        DB::listen(function (QueryExecuted $query) use ($lockedTable, $locked, $started, $parentPid, &$handled): void {
            if ($handled || getmypid() !== $parentPid || ! str_starts_with(strtolower($query->sql), 'update') || ! str_contains($query->sql, $lockedTable)) {
                return;
            }
            $handled = true;
            touch($locked);
            if (! $this->waitForFile($started)) {
                throw new \RuntimeException('Child failed to start its concurrent action.');
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
            touch($started);
            try {
                $childAction();
                file_put_contents($outcomeFile, 'success');
            } catch (ValidationException $e) {
                file_put_contents($outcomeFile, isset($e->errors()['hierarchy_revision']) ? 'stale' : 'validation-error');
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
