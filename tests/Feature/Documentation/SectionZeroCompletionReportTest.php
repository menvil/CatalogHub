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

        $readme = preg_replace('/\s+/', ' ', $this->document('docs/planning/section-00/README.md'));
        self::assertIsString($readme);
        self::assertStringContainsString(
            'Section 0 does not authorize catalog features, deployment redesign, performance certification, or production demo credentials.',
            $readme,
        );

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

        foreach ([
            '98 migrations, three sites, eight users, six memberships, zero catalog records',
            'full PHPUnit: 2,018 tests, 8,103 assertions;',
            'architecture: 72 tests, 759 assertions, zero registered suppressions;',
            'browser: 12 Playwright tests including the 11 Phase 0.16 scenarios;',
            'visual: 28 PHP tests with 578 assertions and one approved Playwright baseline;',
            'Composer and npm audits: zero known vulnerabilities.',
        ] as $evidence) {
            self::assertStringContainsString($evidence, $report);
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
