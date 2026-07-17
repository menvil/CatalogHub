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
        $this->assertSame(['rawSqlExceptions'], array_keys(self::$architecture));
        $entries = self::$architecture['rawSqlExceptions'] ?? null;

        $this->assertIsArray($entries);
        $seen = [];

        foreach ($entries as $entry) {
            $this->assertSame(
                ['class', 'ownerMethods', 'methods', 'reason', 'bindings', 'behaviorTests', 'status'],
                array_keys($entry),
            );
            $this->assertIsString($entry['class'] ?? null);
            $this->assertNotSame('', trim((string) ($entry['reason'] ?? '')));
            $this->assertContains($entry['bindings'] ?? null, ['required', 'literal_only', 'internal_only']);
            $this->assertSame('approved', $entry['status'] ?? null);
            $this->assertNotEmpty($entry['ownerMethods'] ?? []);
            $this->assertNotEmpty($entry['methods'] ?? []);
            $this->assertBehaviorTestsExist($entry['behaviorTests'] ?? []);

            $reflection = new ReflectionClass($entry['class']);
            $this->assertStringStartsWith('App\\Queries\\', $entry['class']);
            $this->assertTrue($reflection->implementsInterface(RawSqlPersistenceBoundary::class));

            foreach ($entry['ownerMethods'] as $ownerMethod) {
                $this->assertTrue($reflection->hasMethod($ownerMethod), "Unknown raw SQL owner method {$entry['class']}::{$ownerMethod}.");
                $source = $this->methodSource($reflection->getMethod($ownerMethod));

                foreach ($entry['methods'] as $method) {
                    $key = $entry['class'].'::'.$ownerMethod.'::'.$method;
                    $this->assertArrayNotHasKey($key, $seen, "Duplicate raw SQL exception {$key}.");
                    $this->assertMatchesRegularExpression('/(?:->|::)\\s*'.preg_quote($method, '/').'\\s*\\(/', $source, "Stale raw SQL exception {$key}.");
                    $seen[$key] = true;
                }
            }
        }
    }

    public function test_approved_raw_sql_behavior_tests_run_in_the_cross_database_suite(): void
    {
        $composer = json_decode(
            (string) file_get_contents(dirname(__DIR__, 4).'/composer.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($composer);
        $scripts = $composer['scripts'] ?? null;
        $this->assertIsArray($scripts);
        $suite = $scripts['test:database-boundaries'] ?? null;
        $this->assertIsArray($suite);
        $command = implode("\n", $suite);

        foreach (self::$architecture['rawSqlExceptions'] ?? [] as $entry) {
            if (($entry['status'] ?? null) !== 'approved') {
                continue;
            }

            foreach ($entry['behaviorTests'] ?? [] as $path) {
                $this->assertStringContainsString(
                    (string) $path,
                    $command,
                    "Approved raw SQL behavior test {$path} is missing from the cross-database suite.",
                );
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
