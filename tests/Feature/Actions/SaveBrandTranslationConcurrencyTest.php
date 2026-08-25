<?php

declare(strict_types=1);

namespace Tests\Feature\Actions;

use App\Actions\Translations\SaveBrandTranslationAction;
use App\Data\Translations\BrandTranslationInput;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Throwable;

final class SaveBrandTranslationConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    public function test_concurrent_saves_upsert_one_brand_locale_row(): void
    {
        if (! function_exists('pcntl_fork') || DB::connection()->getDriverName() === 'sqlite') {
            $this->markTestSkipped('The coordinated two-connection save test runs in the MariaDB and PostgreSQL matrix.');
        }

        $brand = CentralBrand::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $actor = User::factory()->create();
        $coordinationDirectory = sys_get_temp_dir().'/cataloghub-brand-translation-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($coordinationDirectory));

        $parentReady = $coordinationDirectory.'/parent-ready';
        $childReady = $coordinationDirectory.'/child-ready';
        $release = $coordinationDirectory.'/release';
        $childOutcome = $coordinationDirectory.'/child-outcome';
        $connectionName = 'brand_translation_concurrency';
        $defaultConnection = DB::getDefaultConnection();
        config(["database.connections.{$connectionName}" => config("database.connections.{$defaultConnection}")]);

        $childPid = pcntl_fork();
        self::assertNotSame(-1, $childPid);

        if ($childPid === 0) {
            DB::setDefaultConnection($connectionName);
            touch($childReady);

            if (! $this->waitForFile($parentReady, 5.0) || ! $this->waitForFile($release, 5.0)) {
                file_put_contents($childOutcome, 'coordination-timeout');
                exit(0);
            }

            try {
                app(SaveBrandTranslationAction::class)->handle($actor, $brand, $locale, $this->input('Child translation'));
                file_put_contents($childOutcome, 'saved');
            } catch (Throwable $exception) {
                file_put_contents($childOutcome, 'error:'.$exception::class);
            }

            exit(0);
        }

        $parentOutcome = 'saved';

        try {
            touch($parentReady);
            self::assertTrue($this->waitForFile($childReady, 5.0));
            touch($release);
            app(SaveBrandTranslationAction::class)->handle($actor, $brand, $locale, $this->input('Parent translation'));
        } catch (Throwable $exception) {
            $parentOutcome = 'error:'.$exception::class;
        } finally {
            touch($release);
            pcntl_waitpid($childPid, $status);
        }

        self::assertSame('saved', $parentOutcome);
        self::assertSame('saved', file_get_contents($childOutcome));
        self::assertSame(1, BrandTranslation::query()->count());
        self::assertContains(BrandTranslation::query()->sole()->name, ['Parent translation', 'Child translation']);

        BrandTranslation::query()->delete();
        $brand->delete();
        $locale->delete();

        foreach ([$parentReady, $childReady, $release, $childOutcome] as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        rmdir($coordinationDirectory);
    }

    private function input(string $name): BrandTranslationInput
    {
        return new BrandTranslationInput(
            name: $name,
            tagline: null,
            shortDescription: null,
            description: null,
            seoTitle: null,
            seoDescription: null,
            status: TranslationStatus::HumanReviewed,
        );
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
