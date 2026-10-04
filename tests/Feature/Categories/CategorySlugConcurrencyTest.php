<?php

namespace Tests\Feature\Categories;

use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Actions\CentralCatalog\UpdateCentralCategoryAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CategoryHierarchyScope;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

final class CategorySlugConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function beforeTruncatingDatabase(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public static function slugWrites(): array
    {
        return ['create' => ['create'], 'update' => ['update']];
    }

    #[DataProvider('slugWrites')]
    public function test_a_committed_slug_race_returns_validation_and_rolls_back_the_losing_write(string $operation): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('SQLite serializes writers before preflight; this race runs on PostgreSQL/MariaDB.');
        }
        $actor = User::factory()->centralAdmin()->create();
        $subject = CentralCategory::factory()->create(['name' => 'Subject', 'slug' => 'subject', 'position' => 0]);
        $competitor = CentralCategory::factory()->create(['name' => 'Competitor', 'slug' => 'competitor', 'position' => 1]);
        $before = $subject->fresh()->getRawOriginal();
        $scopeKeys = CategoryHierarchyScope::query()->orderBy('scope_key')->pluck('scope_key')->all();
        $directory = sys_get_temp_dir().'/cataloghub-category-slug-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $validated = $directory.'/validated';
        $committed = $directory.'/committed';
        $finished = $directory.'/finished';
        $outcome = $directory.'/outcome';
        $slug = 'raced-slug';
        $parentPid = getmypid();
        $paused = false;
        $defaultConnection = DB::getDefaultConnection();
        $childConnection = 'category_slug_child';
        config(['database.connections.'.$childConnection => config('database.connections.'.$defaultConnection)]);
        DB::listen(function (QueryExecuted $query) use ($slug, $parentPid, $validated, $committed, &$paused): void {
            if ($paused || getmypid() !== $parentPid || ! str_contains(strtolower($query->sql), 'count(')
                || ! str_contains($query->sql, 'central_categories') || ! in_array($slug, $query->bindings, true)) {
                return;
            }
            $paused = true;
            touch($validated);
            if (! $this->waitForFile($committed)) {
                throw new \RuntimeException('Competing slug write did not commit.');
            }
        });
        $pid = pcntl_fork();
        self::assertNotSame(-1, $pid);
        if ($pid === 0) {
            DB::setDefaultConnection($childConnection);
            try {
                if (! $this->waitForFile($validated)) {
                    throw new \RuntimeException('Parent did not finish slug preflight.');
                }
                app(UpdateCentralCategoryAction::class)->handle($actor, $competitor, ['name' => 'Competitor', 'slug' => $slug]);
                file_put_contents($outcome, 'updated');
            } catch (Throwable $exception) {
                file_put_contents($outcome, $exception::class.': '.$exception->getMessage());
            }
            touch($committed);
            // Keep inherited PDO descriptors alive until the parent finishes.
            $this->waitForFile($finished);
            exit(0);
        }
        try {
            try {
                if ($operation === 'create') {
                    app(CreateCentralCategoryAction::class)->handle($actor, ['name' => 'New', 'slug' => $slug, 'parent_id' => $subject->id], 0);
                } else {
                    app(UpdateCentralCategoryAction::class)->handle($actor, $subject, ['name' => 'Changed', 'slug' => $slug]);
                }
                self::fail('Losing slug write succeeded.');
            } catch (ValidationException $exception) {
                self::assertSame(['slug'], array_keys($exception->errors()));
            }
        } finally {
            touch($finished);
            pcntl_waitpid($pid, $status);
        }
        try {
            self::assertTrue($paused);
            self::assertSame(0, pcntl_wexitstatus($status));
            self::assertSame('updated', file_get_contents($outcome));
            DB::purge($defaultConnection);
            self::assertSame($before, $subject->fresh()->getRawOriginal());
            self::assertSame($slug, $competitor->fresh()->slug);
            self::assertSame(2, CentralCategory::query()->count());
            self::assertSame($scopeKeys, CategoryHierarchyScope::query()->orderBy('scope_key')->pluck('scope_key')->all());
            self::assertSame(['catalog.category.updated'], AuditLogEntry::query()->pluck('action')->all());
        } finally {
            DB::disconnect($childConnection);
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
