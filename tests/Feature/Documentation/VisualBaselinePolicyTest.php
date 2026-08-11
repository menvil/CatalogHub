<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Illuminate\Filesystem\Filesystem;
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
        $root = sys_get_temp_dir().'/cataloghub-visual-baseline-'.bin2hex(random_bytes(8));
        $files = new Filesystem;

        try {
            $files->ensureDirectoryExists($root.'/tests/Visual/baselines');
            file_put_contents($root.'/tests/Visual/baselines/reference.png', 'before');
            $this->git($root, 'init -q');
            $this->git($root, 'config user.email test@example.test');
            $this->git($root, 'config user.name Test');
            $this->git($root, 'add .');
            $this->git($root, 'commit -qm baseline');
            file_put_contents($root.'/tests/Visual/baselines/reference.png', 'after');

            putenv('VISUAL_BASELINE_REVIEWED');
            $unreviewed = $this->runBaselineGuard($script, 'HEAD', $root);
            $this->assertSame(1, $unreviewed['exitCode']);
            $this->assertStringContainsString('require explicit review', $unreviewed['output']);

            putenv('VISUAL_BASELINE_REVIEWED=1');
            $reviewed = $this->runBaselineGuard($script, 'HEAD', $root);
            $this->assertSame(0, $reviewed['exitCode'], $reviewed['output']);

            $invalidBase = $this->runBaselineGuard($script, 'not-a-revision', $root);
            $this->assertNotSame(0, $invalidBase['exitCode']);
            $this->assertStringContainsString('Unable to compare visual baselines', $invalidBase['output']);
        } finally {
            putenv($original === false ? 'VISUAL_BASELINE_REVIEWED' : 'VISUAL_BASELINE_REVIEWED='.$original);
            $files->deleteDirectory($root);
        }
    }

    /** @return array{exitCode: int, output: string} */
    private function runBaselineGuard(string $script, string $base, string $root): array
    {
        $output = [];
        $exitCode = 0;
        exec(PHP_BINARY.' '.$script.' '.escapeshellarg($base).' '.escapeshellarg($root).' 2>&1', $output, $exitCode);

        return ['exitCode' => $exitCode, 'output' => implode(PHP_EOL, $output)];
    }

    private function git(string $root, string $command): void
    {
        $output = [];
        $exitCode = 0;
        exec('git -C '.escapeshellarg($root).' '.$command.' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }
}
