<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class PresentationBoundaryTest extends TestCase
{
    /** @var array<string, string> */
    private const ROOTS = [
        'app/Filament/Central' => 'App\\Filament\\Central',
        'app/Filament/Site' => 'App\\Filament\\Site',
        'app/Http/Controllers/Public' => 'App\\Http\\Controllers\\Public',
    ];

    public function test_project_presentation_contexts_have_no_forbidden_imports(): void
    {
        $violations = [];

        foreach (self::ROOTS as $path => $expectedNamespace) {
            foreach ($this->phpFiles(base_path($path)) as $file) {
                $source = (string) file_get_contents($file->getPathname());
                preg_match('/^namespace\s+([^;]+);/m', $source, $namespaceMatch);
                $namespace = $namespaceMatch[1] ?? '';

                self::assertStringStartsWith($expectedNamespace, $namespace, $file->getPathname());

                foreach ($this->importedDependencies($source) as $dependency) {
                    $reason = $this->forbiddenReason($namespace, $dependency);

                    if ($reason !== null) {
                        $violations[] = $file->getPathname().': '.$reason;
                    }
                }
            }
        }

        self::assertSame([], $violations, implode("\n", $violations));
    }

    public function test_multi_import_declaration_detects_a_later_forbidden_dependency(): void
    {
        $source = <<<'PHP'
<?php

namespace App\Filament\Central\Pages;

use App\Services\SharedReadModel, App\Filament\Site\Pages\Home as SiteHome;
PHP;

        $dependencies = $this->importedDependencies($source);

        self::assertSame([
            'App\Services\SharedReadModel',
            'App\Filament\Site\Pages\Home',
        ], $dependencies);
        self::assertNotNull($this->forbiddenReason(
            'App\Filament\Central\Pages\Example',
            $dependencies[1],
        ));
    }

    #[DataProvider('representativeDependencies')]
    public function test_rule_detects_representative_violations_and_allows_shared_dependencies(
        string $source,
        string $dependency,
        bool $allowed,
    ): void {
        self::assertSame($allowed, $this->forbiddenReason($source, $dependency) === null);
    }

    /** @return iterable<string, array{string, string, bool}> */
    public static function representativeDependencies(): iterable
    {
        yield 'Central cannot import Site page' => [
            'App\\Filament\\Central\\Pages\\Example',
            'App\\Filament\\Site\\Pages\\Home',
            false,
        ];
        yield 'Site cannot import Central resource' => [
            'App\\Filament\\Site\\Pages\\Example',
            'App\\Filament\\Central\\Resources\\ProductResource',
            false,
        ];
        yield 'Public cannot import admin UI' => [
            'App\\Http\\Controllers\\Public\\ExampleController',
            'App\\Filament\\Central\\Pages\\Home',
            false,
        ];
        yield 'Central may import shared application code' => [
            'App\\Filament\\Central\\Pages\\Example',
            'App\\Services\\SharedReadModel',
            true,
        ];
        yield 'Site may import shared domain code' => [
            'App\\Filament\\Site\\Pages\\Example',
            'App\\Domains\\Themes\\ThemeLayoutResolver',
            true,
        ];
        yield 'Public may import framework code' => [
            'App\\Http\\Controllers\\Public\\ExampleController',
            'Illuminate\\Http\\Request',
            true,
        ];
    }

    private function forbiddenReason(string $source, string $dependency): ?string
    {
        if (str_starts_with($source, 'App\\Filament\\Central\\')
            && str_starts_with($dependency, 'App\\Filament\\Site\\')) {
            return "Central Admin must not import Site Admin UI: {$dependency}";
        }

        if (str_starts_with($source, 'App\\Filament\\Site\\')
            && str_starts_with($dependency, 'App\\Filament\\Central\\')) {
            return "Site Admin must not import Central Admin UI: {$dependency}";
        }

        if (str_starts_with($source, 'App\\Http\\Controllers\\Public\\')
            && str_starts_with($dependency, 'App\\Filament\\')) {
            return "Public Site must not import admin UI: {$dependency}";
        }

        return null;
    }

    /** @return list<string> */
    private function importedDependencies(string $source): array
    {
        preg_match_all('/^use\s+([^;]+);/m', $source, $imports);
        $dependencies = [];

        foreach ($imports[1] as $import) {
            foreach (preg_split('/\s*,\s*/', trim($import)) ?: [] as $dependency) {
                $dependency = preg_replace('/\s+as\s+.+$/i', '', trim($dependency)) ?? trim($dependency);

                if ($dependency !== '') {
                    $dependencies[] = $dependency;
                }
            }
        }

        return $dependencies;
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $path): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }
}
