<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;

final class ContinuousIntegrationWorkflowTest extends TestCase
{
    public function test_backend_quality_gate_runs_each_backend_layer_and_propagates_failures(): void
    {
        $workflow = $this->workflow();
        $gate = $this->job($workflow, 'backend-quality');
        $tests = $this->job($workflow, 'tests');
        $staticAnalysis = $this->job($workflow, 'static-analysis');

        self::assertStringContainsString('name: Backend quality', $gate);

        foreach (['code-style', 'static-analysis', 'tests'] as $dependency) {
            self::assertStringContainsString('- '.$dependency, $gate);
        }

        foreach (['composer test:unit', 'composer test:legacy-unit', 'composer test:feature'] as $command) {
            self::assertStringContainsString($command, $tests);
        }

        self::assertStringContainsString('composer test:architecture', $staticAnalysis);
        self::assertStringContainsString('composer analyse -- --no-progress', $staticAnalysis);
        self::assertStringContainsString("!= 'success'", $gate);
        self::assertStringNotContainsString('continue-on-error', $gate);
    }

    private function workflow(): string
    {
        $workflow = file_get_contents(dirname(__DIR__, 2).'/.github/workflows/ci.yml');
        self::assertIsString($workflow);

        return $workflow;
    }

    private function job(string $workflow, string $name): string
    {
        $start = strpos($workflow, "  {$name}:\n");
        self::assertIsInt($start, "CI job [{$name}] is not defined.");
        $matched = preg_match('/\n  [a-z0-9-]+:\n/', $workflow, $matches, PREG_OFFSET_CAPTURE, $start + 1);
        $next = $matched === 1 ? $matches[0][1] : false;

        return substr($workflow, $start, $next === false ? null : $next - $start);
    }
}
