<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class ArchitectureDebtReportTest extends TestCase
{
    public function test_json_artifact_exposes_a_numeric_registered_metric_and_detailed_suppressions(): void
    {
        $root = dirname(__DIR__, 2);
        $path = tempnam(sys_get_temp_dir(), 'cataloghub-architecture-report-');
        self::assertIsString($path);

        $process = proc_open(
            [PHP_BINARY, $root.'/tools/architecture/report.php', '--json='.$path],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );
        self::assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);

        try {
            self::assertSame(0, $status, (string) $stderr);
            $report = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

            self::assertIsInt($report['registered']);
            self::assertIsArray($report['suppressions']);
            self::assertSame(count($report['suppressions']), $report['registered']);
            self::assertSame($report, json_decode((string) $stdout, true, flags: JSON_THROW_ON_ERROR));
        } finally {
            unlink($path);
        }
    }
}
