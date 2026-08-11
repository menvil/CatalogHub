<?php

declare(strict_types=1);

namespace Tests\Unit\Foundation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class UnitSuiteIsolationTest extends TestCase
{
    public function test_foundation_unit_baseline_does_not_boot_framework_database_or_network(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php' || $file->getPathname() === __FILE__) {
                continue;
            }

            $source = (string) file_get_contents($file->getPathname());
            $operations = $this->forbiddenOperations($source);

            self::assertSame(
                [],
                $operations,
                "Foundation unit test [{$file->getFilename()}] uses forbidden runtime tokens [".implode(', ', $operations).']',
            );
        }
    }

    public function test_forbidden_symbol_policy_ignores_comments_docblocks_and_strings(): void
    {
        $source = <<<'PHP'
<?php
// Http::get('https://example.test');
/** curl_exec($handle); Tests\TestCase */
$example = "Illuminate\\Support\\Facades\\DB file_get_contents('https://example.test')";
file_get_contents(__FILE__);
PHP;

        self::assertSame([], $this->forbiddenOperations($source));
    }

    /** @return iterable<string, array{string, string}> */
    public static function forbiddenOperationCases(): iterable
    {
        yield 'framework test case' => ['<?php class Example extends Tests\\TestCase {}', 'Tests\\TestCase'];
        yield 'framework testing namespace' => ['<?php use Illuminate\\Foundation\\Testing\\RefreshDatabase;', 'Illuminate\\Foundation\\Testing'];
        yield 'database facade' => ['<?php use Illuminate\\Support\\Facades\\DB;', 'Illuminate\\Support\\Facades\\DB'];
        yield 'Laravel HTTP facade' => ["<?php Http::get('https://example.test');", 'Http::'];
        yield 'curl function' => ['<?php curl_exec($handle);', 'curl_'];
        yield 'URL stream function' => ["<?php file_get_contents('https://example.test/data');", 'URL stream'];
    }

    #[DataProvider('forbiddenOperationCases')]
    public function test_forbidden_symbol_policy_detects_executable_operations(string $source, string $expected): void
    {
        self::assertContains($expected, $this->forbiddenOperations($source));
    }

    /** @return list<string> */
    private function forbiddenOperations(string $source): array
    {
        $tokens = token_get_all($source);
        $forbidden = [];

        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            if (in_array($token[0], [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_NAME_RELATIVE], true)) {
                $reference = ltrim($token[1], '\\');

                foreach ([
                    'Tests\\TestCase',
                    'Illuminate\\Foundation\\Testing',
                    'Illuminate\\Support\\Facades\\DB',
                ] as $boundary) {
                    if ($reference === $boundary || str_starts_with($reference, $boundary.'\\')) {
                        $forbidden[] = $boundary;
                    }
                }

                if (($reference === 'Http' || str_ends_with($reference, '\\Http'))
                    && $this->nextSignificantToken($tokens, $index + 1) === T_DOUBLE_COLON) {
                    $forbidden[] = 'Http::';
                }
            }

            if ($token[0] !== T_STRING) {
                continue;
            }

            $nextIndex = $this->nextSignificantTokenIndex($tokens, $index + 1);
            $next = $nextIndex === null ? null : $tokens[$nextIndex];

            if ($token[1] === 'Http' && is_array($next) && $next[0] === T_DOUBLE_COLON) {
                $forbidden[] = 'Http::';
            }

            if (str_starts_with($token[1], 'curl_') && $next === '(') {
                $forbidden[] = 'curl_';
            }

            if (in_array($token[1], ['file_get_contents', 'fopen', 'file', 'readfile', 'get_headers', 'copy'], true)
                && $next === '('
                && $this->callContainsHttpUrl($tokens, $nextIndex)) {
                $forbidden[] = 'URL stream';
            }
        }

        return array_values(array_unique($forbidden));
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private function callContainsHttpUrl(array $tokens, int $openingParenthesis): bool
    {
        $depth = 0;

        for ($index = $openingParenthesis; $index < count($tokens); $index++) {
            $token = $tokens[$index];

            if ($token === '(') {
                $depth++;
            } elseif ($token === ')' && --$depth === 0) {
                return false;
            } elseif (is_array($token) && $token[0] === T_CONSTANT_ENCAPSED_STRING) {
                $value = substr($token[1], 1, -1);

                if (preg_match('/\Ahttps?:\/\//i', $value) === 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private function nextSignificantToken(array $tokens, int $start): string|int|null
    {
        $index = $this->nextSignificantTokenIndex($tokens, $start);

        if ($index === null) {
            return null;
        }

        $token = $tokens[$index];

        return is_array($token) ? $token[0] : $token;
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private function nextSignificantTokenIndex(array $tokens, int $start): ?int
    {
        for ($index = $start; $index < count($tokens); $index++) {
            $token = $tokens[$index];

            if (! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $index;
            }
        }

        return null;
    }
}
