<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Tests\TestCase;

final class VisualBaselinePolicyTest extends TestCase
{
    public function test_visual_baseline_policy_requires_explicit_review_and_never_auto_accepts(): void
    {
        $policy = (string) file_get_contents(base_path('docs/ui/visual-diff-policy.md'));
        $guard = (string) file_get_contents(base_path('scripts/check-visual-baseline-change.php'));

        $this->assertStringContainsString('no test, seeder, or CI job may overwrite them', $policy);
        $this->assertStringContainsString('VISUAL_BASELINE_REVIEWED', $guard);
    }

    public function test_guard_requires_review_for_a_changed_baseline_and_rejects_an_invalid_base(): void
    {
        $script = escapeshellarg(base_path('scripts/check-visual-baseline-change.php'));
        $original = getenv('VISUAL_BASELINE_REVIEWED');

        try {
            putenv('VISUAL_BASELINE_REVIEWED');
            $unreviewed = $this->runBaselineGuard($script, 'HEAD~1');
            $this->assertSame(1, $unreviewed['exitCode']);
            $this->assertStringContainsString('require explicit review', $unreviewed['output']);

            putenv('VISUAL_BASELINE_REVIEWED=1');
            $reviewed = $this->runBaselineGuard($script, 'HEAD~1');
            $this->assertSame(0, $reviewed['exitCode'], $reviewed['output']);

            $invalidBase = $this->runBaselineGuard($script, 'not-a-revision');
            $this->assertNotSame(0, $invalidBase['exitCode']);
            $this->assertStringContainsString('Unable to compare visual baselines', $invalidBase['output']);
        } finally {
            putenv($original === false ? 'VISUAL_BASELINE_REVIEWED' : 'VISUAL_BASELINE_REVIEWED='.$original);
        }
    }

    /** @return array{exitCode: int, output: string} */
    private function runBaselineGuard(string $script, string $base): array
    {
        $output = [];
        $exitCode = 0;
        exec(PHP_BINARY.' '.$script.' '.escapeshellarg($base).' 2>&1', $output, $exitCode);

        return ['exitCode' => $exitCode, 'output' => implode(PHP_EOL, $output)];
    }
}
