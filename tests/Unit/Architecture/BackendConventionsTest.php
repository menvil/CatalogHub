<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class BackendConventionsTest extends TestCase
{
    public function test_data_transfer_types_do_not_depend_on_http_request_state(): void
    {
        self::assertFalse($this->hasForbiddenDependency('<?php final class TransferData {}', [
            'Illuminate\\Http\\Request',
        ]));
        self::assertTrue($this->hasForbiddenDependency('<?php use Illuminate\\Http\\Request;', [
            'Illuminate\\Http\\Request',
        ]));

        self::assertSame([], $this->violationsInDirectories([
            'app/Data',
            'app/DTO',
            'app/ValueObjects',
        ], ['Illuminate\\Http\\Request']));

        self::assertSame([
            'tests/Fixtures/Architecture/Data/InvalidRequestGlobalData.php',
        ], $this->violationsInDirectories([
            'tests/Fixtures/Architecture/Data',
        ], []));
    }

    public function test_domain_code_does_not_depend_on_presentation_namespaces(): void
    {
        self::assertTrue($this->hasForbiddenDependency('<?php use App\\Filament\\Central\\Pages\\Dashboard;', [
            'App\\Filament\\',
            'App\\Http\\Controllers\\',
        ]));

        self::assertSame([], $this->violationsInDirectories([
            'app/Domains',
        ], ['App\\Filament\\', 'App\\Http\\Controllers\\']));
    }

    public function test_application_and_query_boundaries_use_distinct_names(): void
    {
        self::assertFalse($this->hasExpectedSuffix('AssignSiteMembership', 'Action'));
        self::assertTrue($this->hasExpectedSuffix('AssignSiteMembershipAction', 'Action'));
        self::assertFalse($this->hasExpectedSuffix('SiteContent', 'Query'));
        self::assertTrue($this->hasExpectedSuffix('SiteContentQuery', 'Query'));

        self::assertSame([], $this->boundaryNamingViolations('app/Actions', 'Action'));
        self::assertSame([], $this->boundaryNamingViolations('app/Queries', 'Query'));
    }

    public function test_reference_action_receives_explicit_input_instead_of_request_globals(): void
    {
        $source = $this->file('app/Actions/Auth/UpsertSiteMembershipAction.php');

        self::assertStringContainsString('public function handle(', $source);
        self::assertStringNotContainsString('Illuminate\\Http\\Request', $source);
        self::assertStringNotContainsString('request()', $source);
    }

    /** @param list<string> $forbiddenDependencies */
    private function hasForbiddenDependency(string $source, array $forbiddenDependencies): bool
    {
        foreach ($forbiddenDependencies as $dependency) {
            if (str_contains($source, $dependency)) {
                return true;
            }
        }

        return false;
    }

    private function hasExpectedSuffix(string $className, string $suffix): bool
    {
        return str_ends_with($className, $suffix);
    }

    /** @param list<string> $directories
     * @param  list<string>  $forbiddenDependencies
     * @return list<string>
     */
    private function violationsInDirectories(array $directories, array $forbiddenDependencies): array
    {
        $violations = [];

        foreach ($directories as $directory) {
            foreach ($this->phpFiles($directory) as $file) {
                $source = $this->file($file);

                if ($this->hasForbiddenDependency($source, $forbiddenDependencies) || $this->hasRequestGlobalCall($source)) {
                    $violations[] = $file;
                }
            }
        }

        return $violations;
    }

    private function hasRequestGlobalCall(string $source): bool
    {
        $previous = null;
        $tokens = token_get_all($source);

        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                if (! in_array($token, [' ', "\t", "\n", "\r"], true)) {
                    $previous = $token;
                }

                continue;
            }

            if ($token[0] !== T_STRING || strtolower($token[1]) !== 'request') {
                if (! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $previous = $token[0];
                }

                continue;
            }

            $next = $this->nextMeaningfulToken($tokens, $index + 1);

            if ($next === '(' && ! in_array($previous, [T_OBJECT_OPERATOR, T_DOUBLE_COLON], true)) {
                return true;
            }

            $previous = T_STRING;
        }

        return false;
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private function nextMeaningfulToken(array $tokens, int $offset): int|string|null
    {
        for ($index = $offset; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];

            if (! is_array($token)) {
                if (! in_array($token, [' ', "\t", "\n", "\r"], true)) {
                    return $token;
                }

                continue;
            }

            if (! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $token[0];
            }
        }

        return null;
    }

    /** @return list<string> */
    private function boundaryNamingViolations(string $directory, string $suffix): array
    {
        $violations = [];

        foreach ($this->phpFiles($directory) as $file) {
            if (str_contains($file, '/Concerns/')) {
                continue;
            }

            $className = pathinfo($file, PATHINFO_FILENAME);

            if (! $this->hasExpectedSuffix($className, $suffix)) {
                $violations[] = $file;
            }
        }

        return $violations;
    }

    /** @return list<string> */
    private function phpFiles(string $directory): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path($directory)));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = str_replace(base_path().'/', '', $file->getPathname());
            }
        }

        return $files;
    }

    private function file(string $path): string
    {
        $source = file_get_contents(base_path($path));
        self::assertIsString($source);

        return $source;
    }
}
