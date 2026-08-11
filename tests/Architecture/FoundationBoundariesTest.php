<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class FoundationBoundariesTest extends TestCase
{
    private const PERMISSION_METHODS = [
        'hasCatalogHubPermission',
        'isSuperAdmin',
        'isCentralAdmin',
        'isSiteAdmin',
        'roleEnum',
    ];

    private const RULES = [
        'cross_context_import',
        'request_in_dto',
        'admin_in_domain',
        'raw_permission',
    ];

    public function test_application_has_no_unallowlisted_foundation_boundary_violations(): void
    {
        $root = dirname(__DIR__, 2);
        $allowlist = require __DIR__.'/allowlist.php';
        $violations = array_fill_keys(self::RULES, []);
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app'));

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = str_replace($root.DIRECTORY_SEPARATOR, '', $file->getPathname());

            foreach ($this->violationsFor($relative, (string) file_get_contents($file->getPathname())) as $rule) {
                $violations[$rule][] = $relative;
            }
        }

        foreach (self::RULES as $rule) {
            $allowedFiles = $this->allowedFiles($allowlist[$rule] ?? [], $root);
            $unexpected = array_values(array_diff(array_unique($violations[$rule]), $allowedFiles));
            $stale = array_values(array_diff($allowedFiles, array_unique($violations[$rule])));

            self::assertSame([], $unexpected, "Architecture rule [{$rule}] found new violations.");
            self::assertSame([], $stale, "Architecture rule [{$rule}] has stale allowlist entries.");
        }
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function violationCases(): iterable
    {
        yield 'request object in DTO' => [
            'app/Data/UnsafeData.php',
            '<?php use Illuminate\\Http\\Request;',
            'request_in_dto',
        ];
        yield 'admin import in domain' => [
            'app/Domains/Catalog/UnsafeService.php',
            '<?php use App\\Filament\\Resources\\SiteResource;',
            'admin_in_domain',
        ];
        yield 'central to site presentation import' => [
            'app/Http/Controllers/CentralAdmin/UnsafeController.php',
            '<?php use App\\Http\\Controllers\\SiteAdmin\\DashboardController;',
            'cross_context_import',
        ];
        yield 'raw permission in presentation' => [
            'app/Livewire/UnsafeComponent.php',
            '<?php $user->hasCatalogHubPermission("sites.manage");',
            'raw_permission',
        ];
    }

    #[DataProvider('violationCases')]
    public function test_each_boundary_rule_detects_its_forbidden_shape(
        string $file,
        string $source,
        string $expectedRule,
    ): void {
        self::assertContains($expectedRule, $this->violationsFor($file, $source));
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function ignoredTokenCases(): iterable
    {
        yield 'request references in non-code tokens' => [
            'app/Data/SafeData.php',
            <<<'PHP'
<?php
// use Illuminate\Http\Request;
/** @var Illuminate\Http\Request $request */
$example = 'Illuminate\Http\Request';
PHP,
            'request_in_dto',
        ];
        yield 'admin references in non-code tokens' => [
            'app/Domains/Catalog/SafeService.php',
            <<<'PHP'
<?php
// use App\Filament\Resources\SiteResource;
/** App\Livewire\UnsafeComponent */
$example = 'App\Http\Controllers\CentralAdmin\UnsafeController';
PHP,
            'admin_in_domain',
        ];
        yield 'cross-context references in non-code tokens' => [
            'app/Http/Controllers/CentralAdmin/SafeController.php',
            <<<'PHP'
<?php
// use App\Filament\Site\Pages\Home;
/** App\Http\Controllers\SiteAdmin\UnsafeController */
$example = 'App\Filament\Site\Pages\Home';
PHP,
            'cross_context_import',
        ];
        yield 'permission calls in non-code tokens' => [
            'app/Livewire/SafeComponent.php',
            <<<'PHP'
<?php
// $user->hasCatalogHubPermission('sites.manage');
/** PermissionMatrix and $user->isSuperAdmin() */
$example = '$user->roleEnum()';
PHP,
            'raw_permission',
        ];
    }

    #[DataProvider('ignoredTokenCases')]
    public function test_comments_docblocks_and_strings_do_not_trigger_boundary_rules(
        string $file,
        string $source,
        string $rule,
    ): void {
        self::assertNotContains($rule, $this->violationsFor($file, $source));
    }

    /** @return list<string> */
    private function violationsFor(string $file, string $source): array
    {
        $rules = [];
        $tokens = token_get_all($source);
        $references = $this->namespaceReferences($tokens);

        if (str_starts_with($file, 'app/Data/')
            && $this->hasReference($references, ['Illuminate\\Http\\Request'])) {
            $rules[] = 'request_in_dto';
        }

        if (str_starts_with($file, 'app/Domains/')
            && $this->hasReference($references, ['App\\Filament', 'App\\Http\\Controllers', 'App\\Livewire'])) {
            $rules[] = 'admin_in_domain';
        }

        $centralFile = str_starts_with($file, 'app/Filament/Central/')
            || str_starts_with($file, 'app/Http/Controllers/CentralAdmin/');
        $siteFile = str_starts_with($file, 'app/Filament/Site/')
            || str_starts_with($file, 'app/Http/Controllers/SiteAdmin/');

        if (($centralFile && $this->hasReference($references, ['App\\Filament\\Site', 'App\\Http\\Controllers\\SiteAdmin']))
            || ($siteFile && $this->hasReference($references, ['App\\Filament\\Central', 'App\\Http\\Controllers\\CentralAdmin']))) {
            $rules[] = 'cross_context_import';
        }

        $presentationFile = str_starts_with($file, 'app/Filament/')
            || str_starts_with($file, 'app/Http/Controllers/')
            || str_starts_with($file, 'app/Livewire/');

        if ($presentationFile && ($this->hasPermissionMatrixReference($tokens, $references)
            || $this->hasPermissionMethodCall($tokens))) {
            $rules[] = 'raw_permission';
        }

        return $rules;
    }

    /**
     * @param  list<array{int, string, int}|string>  $tokens
     * @return list<string>
     */
    private function namespaceReferences(array $tokens): array
    {
        $references = [];

        foreach ($tokens as $token) {
            if (is_array($token) && in_array($token[0], [T_NAME_FULLY_QUALIFIED, T_NAME_QUALIFIED, T_NAME_RELATIVE], true)) {
                $references[] = ltrim($token[1], '\\');
            }
        }

        return $references;
    }

    /**
     * @param  list<string>  $references
     * @param  list<string>  $boundaries
     */
    private function hasReference(array $references, array $boundaries): bool
    {
        foreach ($references as $reference) {
            foreach ($boundaries as $boundary) {
                if ($reference === $boundary || str_starts_with($reference, $boundary.'\\')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  list<array{int, string, int}|string>  $tokens
     * @param  list<string>  $references
     */
    private function hasPermissionMatrixReference(array $tokens, array $references): bool
    {
        foreach ($references as $reference) {
            if ($reference === 'PermissionMatrix' || str_ends_with($reference, '\\PermissionMatrix')) {
                return true;
            }
        }

        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_STRING && $token[1] === 'PermissionMatrix') {
                return true;
            }
        }

        return false;
    }

    /** @param list<array{int, string, int}|string> $tokens */
    private function hasPermissionMethodCall(array $tokens): bool
    {
        foreach ($tokens as $index => $token) {
            if (! is_array($token) || $token[0] !== T_STRING || ! in_array($token[1], self::PERMISSION_METHODS, true)) {
                continue;
            }

            $parenthesisIndex = $this->nextSignificantTokenIndex($tokens, $index + 1);

            if ($parenthesisIndex === null || $tokens[$parenthesisIndex] !== '(') {
                continue;
            }

            if ($token[1] === 'hasCatalogHubPermission') {
                return true;
            }

            $operatorIndex = $this->previousSignificantTokenIndex($tokens, $index - 1);
            $operator = $operatorIndex === null ? null : $tokens[$operatorIndex];

            if (is_array($operator) && in_array($operator[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{int, string, int}|string>  $tokens
     */
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

    /** @param list<array{int, string, int}|string> $tokens */
    private function previousSignificantTokenIndex(array $tokens, int $start): ?int
    {
        for ($index = $start; $index >= 0; $index--) {
            $token = $tokens[$index];

            if (! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  list<array{file?: mixed, owner?: mixed, reason?: mixed, task?: mixed}>  $entries
     * @return list<string>
     */
    private function allowedFiles(array $entries, string $root): array
    {
        $files = [];

        foreach ($entries as $entry) {
            foreach (['file', 'owner', 'reason', 'task'] as $field) {
                self::assertIsString($entry[$field] ?? null, "Architecture allowlist field [{$field}] is required.");
                self::assertNotSame('', trim($entry[$field]), "Architecture allowlist field [{$field}] cannot be empty.");
            }

            $file = $entry['file'];
            self::assertStringStartsWith('app/', $file);
            self::assertStringEndsWith('.php', $file);
            self::assertFileExists($root.'/'.$file);
            $files[] = $file;
        }

        self::assertSame($files, array_values(array_unique($files)), 'Architecture allowlist files must be unique per rule.');

        return $files;
    }
}
