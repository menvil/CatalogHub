<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class BaselineDocumentationTest extends TestCase
{
    #[DataProvider('requiredDocuments')]
    public function test_required_section_zero_document_exists_and_names_its_task(string $path, string $task): void
    {
        $contents = file_get_contents($this->rootPath($path));

        self::assertIsString($contents);
        self::assertStringContainsString($task, $contents);
    }

    public function test_section_index_only_links_to_existing_local_markdown_documents(): void
    {
        $indexPath = $this->rootPath('docs/planning/section-00/README.md');
        $contents = file_get_contents($indexPath);

        self::assertIsString($contents);
        preg_match_all('/\[[^]]+]\((?!https?:\/\/)([^)#]+\.md)(?:#[^)]+)?\)/', $contents, $matches);
        self::assertNotEmpty($matches[1]);

        foreach ($matches[1] as $target) {
            self::assertFileExists(dirname($indexPath).'/'.$target, "Broken local documentation link: {$target}");
        }
    }

    /** @return array<string, array{string, string}> */
    public static function requiredDocuments(): array
    {
        return [
            'runtime inventory' => ['docs/planning/section-00/baseline/runtime-versions.md', 'P00-001'],
            'route inventory' => ['docs/planning/section-00/baseline/routes-and-panels.md', 'P00-002'],
            'domain inventory' => ['docs/planning/section-00/baseline/domain-and-database.md', 'P00-003'],
            'quality inventory' => ['docs/planning/section-00/baseline/tests-and-ci.md', 'P00-004'],
            'check results' => ['docs/planning/section-00/baseline/check-results.md', 'P00-005'],
            'section report' => ['docs/planning/section-00/README.md', 'P00-006'],
            'ADR index' => ['docs/architecture/adr/README.md', 'P00-006'],
        ];
    }

    private function rootPath(string $path): string
    {
        return dirname(__DIR__, 3).'/'.$path;
    }
}
