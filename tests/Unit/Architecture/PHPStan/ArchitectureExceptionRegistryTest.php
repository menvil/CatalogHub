<?php

namespace Tests\Unit\Architecture\PHPStan;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use PHPStan\Testing\PHPStanTestCase;
use ReflectionClass;
use ReflectionMethod;

class ArchitectureExceptionRegistryTest extends PHPStanTestCase
{
    /** @var array<string, mixed> */
    private static array $architecture;

    /** @return list<string> */
    public static function getAdditionalConfigFiles(): array
    {
        return [dirname(__DIR__, 4).'/phpstan.neon'];
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $architecture = self::getContainer()->getParameter('architecture');
        self::assertIsArray($architecture);
        self::$architecture = $architecture;
    }

    public static function tearDownAfterClass(): void
    {
        restore_error_handler();
        restore_exception_handler();

        parent::tearDownAfterClass();
    }

    public function test_raw_sql_registry_entries_are_exact_tested_and_not_stale(): void
    {
        $entries = self::$architecture['rawSqlExceptions'] ?? null;

        $this->assertIsArray($entries);
        $seen = [];

        foreach ($entries as $entry) {
            $this->assertIsString($entry['class'] ?? null);
            $this->assertNotSame('', trim((string) ($entry['reason'] ?? '')));
            $this->assertContains($entry['bindings'] ?? null, ['required', 'literal_only', 'internal_only']);
            $this->assertContains($entry['status'] ?? null, ['approved', 'legacy']);
            $this->assertNotEmpty($entry['methods'] ?? []);
            $this->assertBehaviorTestsExist($entry['behaviorTests'] ?? []);

            $reflection = new ReflectionClass($entry['class']);
            $source = file_get_contents((string) $reflection->getFileName());
            $this->assertIsString($source);

            if ($entry['status'] === 'approved') {
                $this->assertTrue($reflection->implementsInterface(RawSqlPersistenceBoundary::class));
            } else {
                $this->assertNotSame('', trim((string) ($entry['target'] ?? '')));
            }

            foreach ($entry['methods'] as $method) {
                $key = $entry['class'].'::'.$method;
                $this->assertArrayNotHasKey($key, $seen, "Duplicate raw SQL exception {$key}.");
                $this->assertMatchesRegularExpression('/->\\s*'.preg_quote($method, '/').'\\s*\\(/', $source, "Stale raw SQL exception {$key}.");
                $seen[$key] = true;
            }
        }
    }

    public function test_legacy_architecture_entries_are_exact_and_not_stale(): void
    {
        $this->assertLegacyMethodEntries(
            self::$architecture['controllerValidationExceptions'] ?? null,
            '->validate(',
        );
        $this->assertLegacyMethodEntries(
            self::$architecture['controllerPermissionExceptions'] ?? null,
            'hasCatalogHubPermission(',
        );
        $this->assertLowLevelQueryEntries(self::$architecture['lowLevelQueryExceptions'] ?? null);
    }

    private function assertLegacyMethodEntries(mixed $entries, string $expectedCall): void
    {
        $this->assertIsArray($entries);
        $seen = [];

        foreach ($entries as $entry) {
            $this->assertNotSame('', trim((string) ($entry['reason'] ?? '')));
            $this->assertNotSame('', trim((string) ($entry['target'] ?? '')));

            $reflection = new ReflectionClass($entry['class']);

            foreach ($entry['methods'] as $method) {
                $key = $entry['class'].'::'.$method;
                $this->assertArrayNotHasKey($key, $seen, "Duplicate architecture exception {$key}.");
                $this->assertStringContainsString($expectedCall, $this->methodSource($reflection->getMethod($method)), "Stale architecture exception {$key}.");
                $seen[$key] = true;
            }
        }
    }

    private function assertLowLevelQueryEntries(mixed $entries): void
    {
        $this->assertIsArray($entries);
        $seen = [];

        foreach ($entries as $entry) {
            $this->assertNotSame('', trim((string) ($entry['reason'] ?? '')));
            $this->assertNotSame('', trim((string) ($entry['target'] ?? '')));
            $this->assertBehaviorTestsExist($entry['behaviorTests'] ?? []);

            $reflection = new ReflectionClass($entry['class']);
            $source = file_get_contents((string) $reflection->getFileName());
            $this->assertIsString($source);

            foreach ($entry['methods'] as $method) {
                $key = $entry['class'].'::DB::'.$method;
                $this->assertArrayNotHasKey($key, $seen, "Duplicate low-level query exception {$key}.");
                $this->assertMatchesRegularExpression('/DB::'.preg_quote($method, '/').'\\s*\\(/', $source, "Stale low-level query exception {$key}.");
                $seen[$key] = true;
            }
        }
    }

    private function assertBehaviorTestsExist(mixed $paths): void
    {
        $this->assertIsArray($paths);
        $this->assertNotEmpty($paths);

        foreach ($paths as $path) {
            $this->assertFileExists(dirname(__DIR__, 4).'/'.$path);
        }
    }

    private function methodSource(ReflectionMethod $method): string
    {
        $lines = file((string) $method->getFileName());
        $this->assertIsArray($lines);

        return implode('', array_slice(
            $lines,
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1,
        ));
    }
}
