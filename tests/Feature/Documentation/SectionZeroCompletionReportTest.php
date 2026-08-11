<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use PHPUnit\Framework\TestCase;

final class SectionZeroCompletionReportTest extends TestCase
{
    public function test_phase_registry_covers_every_section_zero_task_exactly_once(): void
    {
        $report = $this->document('docs/planning/section-00/completion-report.md');
        preg_match_all('/^\| 0\.\d+ \| `P00-(\d{3})–P00-(\d{3})`/m', $report, $matches, PREG_SET_ORDER);
        $taskIds = [];

        foreach ($matches as [, $first, $last]) {
            array_push($taskIds, ...range((int) $first, (int) $last));
        }

        self::assertSame(range(1, 134), $taskIds);
    }

    public function test_completion_documents_link_real_evidence_and_record_handoff_constraints(): void
    {
        $paths = [
            'docs/planning/section-00/README.md',
            'docs/planning/section-00/completion-report.md',
        ];

        foreach ($paths as $path) {
            $document = $this->document($path);
            preg_match_all('/\[[^]]+]\(([^)#]+)(?:#[^)]+)?\)/', $document, $links);

            foreach ($links[1] as $link) {
                if (preg_match('/\Ahttps?:\/\//', $link) === 1) {
                    continue;
                }

                self::assertFileExists(dirname($this->root().'/'.$path).'/'.$link, "Broken documentation link [{$link}] in [{$path}].");
            }
        }

        $report = $this->document('docs/planning/section-00/completion-report.md');

        foreach ([
            'Actual implementation paths',
            'Deviations and compatibility ownership',
            'Unresolved blockers',
            'Brands handoff prerequisites',
            'P00-092–P00-095',
            'FoundationDemoSeeder',
            'composer bootstrap:foundation',
            'No blocking Section Zero defect remains open',
        ] as $contract) {
            self::assertStringContainsString($contract, $report);
        }
    }

    private function document(string $path): string
    {
        $contents = file_get_contents($this->root().'/'.$path);
        self::assertIsString($contents);

        return $contents;
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
