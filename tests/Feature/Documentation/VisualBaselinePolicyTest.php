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
}
