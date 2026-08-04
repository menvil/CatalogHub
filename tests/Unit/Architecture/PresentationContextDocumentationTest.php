<?php

declare(strict_types=1);

namespace Tests\Unit\Architecture;

use Filament\Facades\Filament;
use Tests\TestCase;

final class PresentationContextDocumentationTest extends TestCase
{
    private const DOCUMENT = 'docs/architecture/presentation-contexts.md';

    public function test_context_document_exists_and_matches_registered_boundaries(): void
    {
        $contents = (string) file_get_contents(base_path(self::DOCUMENT));

        foreach (['Central Admin', 'Site Admin', 'Public Site', '/admin/central', '/admin/site', 'public.home'] as $contract) {
            self::assertStringContainsString($contract, $contents);
        }

        foreach (Filament::getPanels() as $id => $panel) {
            self::assertStringContainsString("`{$id}`", $contents);
            self::assertStringContainsString('`/'.$panel->getPath().'`', $contents);
        }

        self::assertCount(2, Filament::getPanels(), 'Every registered panel must be documented explicitly.');
        self::assertNotNull(app('router')->getRoutes()->getByName('public.home'));
        self::assertStringContainsString('P00-027', $contents);
        self::assertStringContainsString('P00-029', $contents);
        self::assertStringContainsString('```mermaid', $contents);
    }

    public function test_document_local_markdown_links_resolve(): void
    {
        $path = base_path(self::DOCUMENT);
        $contents = (string) file_get_contents($path);
        preg_match_all('/\[[^]]+]\((?!https?:\/\/)([^)#]+\.md)(?:#[^)]+)?\)/', $contents, $matches);

        self::assertNotEmpty($matches[1]);

        foreach ($matches[1] as $target) {
            self::assertFileExists(dirname($path).'/'.$target, "Broken documentation link: {$target}");
        }
    }
}
