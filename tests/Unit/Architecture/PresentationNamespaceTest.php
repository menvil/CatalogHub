<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\TestCase;

final class PresentationNamespaceTest extends TestCase
{
    public function test_presentation_context_roots_are_explicit_and_documented(): void
    {
        foreach ([
            'app/Filament/Central' => 'App\\Filament\\Central',
            'app/Filament/Site' => 'App\\Filament\\Site',
            'app/Http/Controllers/Public' => 'App\\Http\\Controllers\\Public',
        ] as $path => $namespace) {
            self::assertDirectoryExists(base_path($path));
            self::assertFileExists(base_path($path.'/README.md'));
            self::assertStringContainsString(
                $namespace,
                (string) file_get_contents(base_path($path.'/README.md')),
            );
        }
    }

    public function test_only_approved_context_directories_are_introduced_below_filament(): void
    {
        $directories = glob(app_path('Filament/*'), GLOB_ONLYDIR);

        self::assertIsArray($directories);
        $contextDirectories = array_values(array_filter(
            array_map('basename', $directories),
            static fn (string $directory): bool => in_array($directory, ['Central', 'Site'], true),
        ));
        sort($contextDirectories);

        self::assertSame(['Central', 'Site'], $contextDirectories);
    }
}
