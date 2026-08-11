<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class FoundationBoundariesTest extends TestCase
{
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

    /** @return list<string> */
    private function violationsFor(string $file, string $source): array
    {
        $rules = [];

        if (str_starts_with($file, 'app/Data/')
            && str_contains($source, 'Illuminate\\Http\\Request')) {
            $rules[] = 'request_in_dto';
        }

        if (str_starts_with($file, 'app/Domains/')
            && preg_match('/App\\\\(?:Filament|Http\\\\Controllers|Livewire)\\\\/', $source) === 1) {
            $rules[] = 'admin_in_domain';
        }

        $centralFile = str_starts_with($file, 'app/Filament/Central/')
            || str_starts_with($file, 'app/Http/Controllers/CentralAdmin/');
        $siteFile = str_starts_with($file, 'app/Filament/Site/')
            || str_starts_with($file, 'app/Http/Controllers/SiteAdmin/');

        if (($centralFile && preg_match('/App\\\\(?:Filament\\\\Site|Http\\\\Controllers\\\\SiteAdmin)\\\\/', $source) === 1)
            || ($siteFile && preg_match('/App\\\\(?:Filament\\\\Central|Http\\\\Controllers\\\\CentralAdmin)\\\\/', $source) === 1)) {
            $rules[] = 'cross_context_import';
        }

        $presentationFile = str_starts_with($file, 'app/Filament/')
            || str_starts_with($file, 'app/Http/Controllers/')
            || str_starts_with($file, 'app/Livewire/');

        if ($presentationFile && preg_match('/PermissionMatrix|hasCatalogHubPermission\s*\(|->(?:isSuperAdmin|isCentralAdmin|isSiteAdmin|roleEnum)\s*\(/', $source) === 1) {
            $rules[] = 'raw_permission';
        }

        return $rules;
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
