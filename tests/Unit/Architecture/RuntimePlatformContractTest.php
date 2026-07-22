<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimePlatformContractTest extends TestCase
{
    private const MINIMUM_PHP_VERSION = '8.5.0';

    private const MINIMUM_CI_PHP_VERSION = '8.5';

    private const MINIMUM_POSTGRES_VERSION = '18.4';

    #[Test]
    public function composer_and_the_test_runtime_require_php_85_or_newer(): void
    {
        $composer = $this->jsonFile('composer.json');
        $lock = $this->jsonFile('composer.lock');

        self::assertGreaterThanOrEqual(80500, PHP_VERSION_ID);
        self::assertSame('^8.5', $composer['require']['php'] ?? null);
        self::assertSame('^8.5', $lock['platform']['php'] ?? null);
    }

    #[Test]
    public function every_ci_php_runtime_meets_the_supported_minimum(): void
    {
        foreach (['.github/workflows/ci.yml', '.github/workflows/coverage.yml'] as $path) {
            $contents = $this->file($path);
            preg_match_all('/php-version:\\s*[\'\"]?([^\'\"\\s]+)/', $contents, $matches);

            self::assertNotEmpty($matches[1], "No PHP runtime is configured in {$path}.");

            foreach ($matches[1] as $version) {
                self::assertTrue(
                    version_compare($version, self::MINIMUM_CI_PHP_VERSION, '>='),
                    "{$path} uses unsupported PHP {$version}; minimum is ".self::MINIMUM_PHP_VERSION.'.',
                );
            }

            self::assertStringNotContainsString('php-8.3', $contents);
        }
    }

    #[Test]
    public function postgres_images_are_pinned_to_184_or_newer(): void
    {
        foreach (['.github/workflows/ci.yml', 'docker-compose.yml'] as $path) {
            $contents = $this->file($path);
            preg_match_all('/image:\\s*postgres:([^\\s]+)/', $contents, $matches);

            self::assertNotEmpty($matches[1], "No pinned PostgreSQL image is configured in {$path}.");

            foreach ($matches[1] as $tag) {
                self::assertMatchesRegularExpression(
                    '/^(\\d+\\.\\d+)-alpine$/',
                    $tag,
                    "{$path} must pin PostgreSQL to an explicit major and minor Alpine image.",
                );
                preg_match('/^(\\d+\\.\\d+)-alpine$/', $tag, $versionMatch);
                $version = $versionMatch[1];

                self::assertTrue(
                    version_compare($version, self::MINIMUM_POSTGRES_VERSION, '>='),
                    "{$path} uses unsupported PostgreSQL {$version}; minimum is ".self::MINIMUM_POSTGRES_VERSION.'.',
                );
            }
        }
    }

    #[Test]
    public function production_defaults_to_postgres_and_ci_verifies_the_server_version(): void
    {
        self::assertMatchesRegularExpression('/^DB_CONNECTION=pgsql$/m', $this->file('.env.example'));
        self::assertMatchesRegularExpression('/^DB_CONNECTION=pgsql$/m', $this->file('.env.production.example'));
        self::assertStringContainsString("env('DB_CONNECTION', 'pgsql')", $this->file('config/database.php'));
        self::assertStringContainsString('php artisan cataloghub:platform-check', $this->file('.github/workflows/ci.yml'));
    }

    #[Test]
    public function runtime_minimums_are_centralized_in_platform_configuration(): void
    {
        $platform = require $this->rootPath('config/platform.php');

        self::assertIsArray($platform);
        self::assertSame(self::MINIMUM_PHP_VERSION, $platform['minimum_php_version'] ?? null);
        self::assertSame(80500, $platform['minimum_php_version_id'] ?? null);
        self::assertSame(self::MINIMUM_POSTGRES_VERSION, $platform['minimum_postgres_version'] ?? null);
        self::assertSame(180004, $platform['minimum_postgres_version_num'] ?? null);
    }

    /** @return array<string, mixed> */
    private function jsonFile(string $path): array
    {
        $decoded = json_decode($this->file($path), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function file(string $path): string
    {
        $contents = file_get_contents($this->rootPath($path));
        self::assertIsString($contents);

        return $contents;
    }

    private function rootPath(string $path): string
    {
        return dirname(__DIR__, 3).'/'.$path;
    }
}
