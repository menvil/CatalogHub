<?php

namespace Tests\Feature\Attributes;

use App\Actions\CategorySchema\AssignAttributeToCategoryAction;
use App\Actions\CategorySchema\CreateGlobalAttributeDefinitionAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\MoveCategoryAttributeAssignmentAction;
use App\Actions\CategorySchema\UpdateCategoryAttributeAssignmentAction;
use App\Actions\CategorySchema\UpdateGlobalAttributeDefinitionAction;
use App\Actions\Translations\SaveAttributeOptionTranslationAction;
use App\Actions\Translations\SaveAttributeSectionTranslationAction;
use App\Actions\Translations\SaveAttributeTranslationAction;
use App\Actions\Translations\SaveCategoryTranslationAction;
use App\Actions\Translations\SaveUnitTranslationAction;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use Throwable;

final class AttributeGlobalizationConcurrencyTest extends TestCase
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

    public function test_concurrent_assignments_serialize_and_reject_stale_membership(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $category = CentralCategory::factory()->create();
        $definition = AttributeDefinition::factory()->global()->create();
        $this->race('attribute_identity_scopes',
            fn () => app(AssignAttributeToCategoryAction::class)->handle($category, $definition, [], 1, $actor),
            fn () => app(AssignAttributeToCategoryAction::class)->handle($category, $definition, [], 1, $actor),
            function (string $outcome) use ($category): void {
                self::assertSame('stale', $outcome);
                self::assertSame(1, CategoryAttributeAssignment::query()->count());
                self::assertSame(2, CentralCategory::query()->findOrFail($category->id)->schema_revision);
            });
    }

    public function test_concurrent_configuration_and_reorder_rejects_stale_writer(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $this->race('attribute_identity_scopes',
            fn () => app(UpdateCategoryAttributeAssignmentAction::class)->handle($assignment, ['is_visible' => false], 1, $actor),
            fn () => app(MoveCategoryAttributeAssignmentAction::class)->handle($assignment, null, 0, 1, $actor),
            function (string $outcome) use ($assignment): void {
                self::assertSame('stale', $outcome);
                self::assertFalse(CategoryAttributeAssignment::query()->findOrFail($assignment->id)->is_visible);
                self::assertSame(2, CentralCategory::query()->findOrFail($assignment->central_category_id)->schema_revision);
            });
    }

    public function test_global_edit_serializes_with_assignment_configuration(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $definition = $assignment->definition;
        $this->race('attribute_identity_scopes',
            fn () => app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, ['name' => 'Canonical edit'], $actor),
            fn () => app(UpdateCategoryAttributeAssignmentAction::class)->handle($assignment, ['is_required' => true], 1, $actor),
            function (string $outcome) use ($assignment, $definition): void {
                self::assertSame('stale', $outcome);
                self::assertFalse(CategoryAttributeAssignment::query()->findOrFail($assignment->id)->is_required);
                self::assertSame('Canonical edit', AttributeDefinition::query()->findOrFail($definition->id)->name);
            });
    }

    public function test_fanout_discovers_assignment_committed_by_concurrent_writer(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $definition = $assignment->definition;
        $other = CentralCategory::factory()->create();
        $this->race('attribute_identity_scopes',
            fn () => app(AssignAttributeToCategoryAction::class)->handle($other, $definition, [], 1, $actor),
            fn () => app(UpdateGlobalAttributeDefinitionAction::class)->handle($definition, ['name' => 'Canonical edit'], $actor),
            function (string $outcome) use ($assignment, $other): void {
                self::assertSame('success', $outcome);
                self::assertSame(2, CentralCategory::query()->findOrFail($assignment->central_category_id)->schema_revision);
                self::assertSame(3, CentralCategory::query()->findOrFail($other->id)->schema_revision);
                self::assertSame(3, AuditLogEntry::query()->where('action', 'catalog.category.schema.invalidated')->count());
            });
    }

    public function test_global_edit_fanout_and_schema_review_serialize_without_stale_approval(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $assignment = CategoryAttributeAssignment::factory()->create();
        $category = $assignment->category;
        $this->race('attribute_identity_scopes',
            fn () => app(MarkCategorySchemaReviewedAction::class)->handle($category, 1, $actor),
            fn () => app(UpdateGlobalAttributeDefinitionAction::class)->handle($assignment->definition, ['name' => 'Canonical edit'], $actor),
            function (string $outcome) use ($category): void {
                self::assertSame('success', $outcome);
                self::assertSame('draft', CentralCategory::query()->findOrFail($category->id)->schema_status->value);
                self::assertNull(CentralCategory::query()->findOrFail($category->id)->schema_reviewed_revision);
                self::assertSame(2, CentralCategory::query()->findOrFail($category->id)->schema_revision);
            });
    }

    public function test_unique_global_code_race_has_one_winner(): void
    {
        $actor = User::factory()->centralAdmin()->create();
        $data = ['code' => 'global_code', 'name' => 'Global', 'data_type' => 'string'];
        $this->race('attribute_identity_scopes',
            fn () => app(CreateGlobalAttributeDefinitionAction::class)->handle($data, $actor),
            fn () => app(CreateGlobalAttributeDefinitionAction::class)->handle($data, $actor),
            function (string $outcome): void {
                self::assertSame('validation-error', $outcome);
                self::assertSame(1, AttributeDefinition::query()->where('code', 'global_code')->count());
                self::assertSame(1, AuditLogEntry::query()->where('action', 'catalog.attribute.created')->count());
            });
    }

    #[DataProvider('translationOwners')]
    public function test_translation_identity_check_and_creation_serialize_even_with_deferred_key(string $type, string $table, string $ownerColumn, string $field): void
    {
        $owner = match ($type) {
            'category' => CentralCategory::factory()->create(),
            'attribute' => AttributeDefinition::factory()->global()->create(),
            'section' => AttributeSection::factory()->create(),
            'option' => AttributeOption::factory()->create(),
            'unit' => MeasurementUnit::factory()->create(),
            default => throw new \LogicException('Unexpected translation owner.'),
        };
        $locale = Locale::factory()->create();
        if ($type !== 'option') {
            Schema::table($table, fn ($blueprint) => $blueprint->dropUnique($table.'_owner_locale_id_unique'));
        }
        $this->race($owner->getTable(),
            fn () => $this->saveTranslation($owner, $locale, [$field => 'Parent']),
            fn () => $this->saveTranslation($owner, $locale, [$field => 'Child']),
            function (string $outcome) use ($owner, $locale, $table, $ownerColumn, $field): void {
                self::assertSame('success', $outcome);
                $rows = DB::table($table)->where($ownerColumn, $owner->id)->where('locale_id', $locale->id)->get();
                self::assertCount(1, $rows);
                self::assertSame('Child', $rows->sole()->$field);
            });
    }

    public static function translationOwners(): array
    {
        return [
            ['category', 'category_translations', 'category_id', 'name'],
            ['attribute', 'attribute_translations', 'attribute_definition_id', 'label'],
            ['section', 'attribute_section_translations', 'attribute_section_id', 'name'],
            ['option', 'attribute_option_translations', 'attribute_option_id', 'label'],
            ['unit', 'unit_translations', 'measurement_unit_id', 'short_name'],
        ];
    }

    /** @param array<string, mixed> $data */
    private function saveTranslation(Model $owner, Locale $locale, array $data): Model
    {
        return match (true) {
            $owner instanceof CentralCategory => app(SaveCategoryTranslationAction::class)->handle($owner, $locale, $data),
            $owner instanceof AttributeDefinition => app(SaveAttributeTranslationAction::class)->handle($owner, $locale, $data),
            $owner instanceof AttributeSection => app(SaveAttributeSectionTranslationAction::class)->handle($owner, $locale, $data),
            $owner instanceof AttributeOption => app(SaveAttributeOptionTranslationAction::class)->handle($owner, $locale, $data),
            $owner instanceof MeasurementUnit => app(SaveUnitTranslationAction::class)->handle($owner, $locale, $data),
            default => throw new \LogicException('Unexpected translation owner.'),
        };
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
        $directory = sys_get_temp_dir().'/cataloghub-attribute-lock-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));
        $originalConnection = DB::getDefaultConnection();
        $defaultConnection = $originalConnection;
        $sqlitePath = null;
        // Canonical SQLite runs on a real shared file for this test. In-memory
        // connections cannot prove exclusion between independent processes.
        if (DB::connection()->getDriverName() === 'sqlite') {
            $sqlitePath = $directory.'/database.sqlite';
            DB::statement("VACUUM INTO '".$sqlitePath."'");
            $defaultConnection = 'attribute_parent_file';
            config(['database.connections.'.$defaultConnection => [
                'driver' => 'sqlite', 'database' => $sqlitePath, 'foreign_key_constraints' => true,
                'busy_timeout' => 10000,
            ]]);
            DB::setDefaultConnection($defaultConnection);
        }
        $childConnection = 'attribute_child_connection';
        config(['database.connections.'.$childConnection => config('database.connections.'.$defaultConnection)]);
        $locked = $directory.'/locked';
        $started = $directory.'/child-lock-query-started';
        $lockTime = $directory.'/child-lock-query-time';
        $outcomeFile = $directory.'/outcome';
        $parentPid = getmypid();
        $handled = false;
        DB::listen(function (QueryExecuted $query) use ($lockedTable, $locked, $started, $lockTime, $parentPid, &$handled): void {
            if ($handled || getmypid() !== $parentPid || ! str_starts_with(strtolower($query->sql), 'update') || ! str_contains($query->sql, $lockedTable)) {
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
                if (! $attempted && str_starts_with(strtolower($sql), 'update') && str_contains($sql, $lockedTable)) {
                    $attempted = true;
                    touch($started);
                }
            });
            $timed = false;
            DB::listen(function (QueryExecuted $query) use ($lockedTable, $lockTime, &$timed): void {
                if (! $timed && str_starts_with(strtolower($query->sql), 'update') && str_contains($query->sql, $lockedTable)) {
                    $timed = true;
                    file_put_contents($lockTime, (string) $query->time);
                }
            });
            try {
                $childAction();
                file_put_contents($outcomeFile, 'success');
            } catch (ValidationException $e) {
                file_put_contents($outcomeFile, isset($e->errors()['schema_revision']) ? 'stale' : 'validation-error');
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
