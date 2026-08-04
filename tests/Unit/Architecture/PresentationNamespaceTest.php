<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Tests\TestCase;

final class PresentationNamespaceTest extends TestCase
{
    private const APPROVED_FILAMENT_DIRECTORIES = [
        'Central',
        'Concerns',
        'Pages',
        'Resources',
        'Site',
        'Support',
        'Widgets',
    ];

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

    public function test_filament_directory_inventory_matches_the_explicit_allowlist(): void
    {
        $directories = glob(app_path('Filament/*'), GLOB_ONLYDIR);

        self::assertIsArray($directories);
        $actualDirectories = array_map('basename', $directories);
        sort($actualDirectories);

        self::assertSame(self::APPROVED_FILAMENT_DIRECTORIES, $actualDirectories);
    }

    public function test_an_unapproved_filament_directory_is_rejected_by_the_allowlist(): void
    {
        $actualDirectories = [...self::APPROVED_FILAMENT_DIRECTORIES, 'UnapprovedContext'];
        sort($actualDirectories);

        self::assertNotSame(self::APPROVED_FILAMENT_DIRECTORIES, $actualDirectories);
    }
}
