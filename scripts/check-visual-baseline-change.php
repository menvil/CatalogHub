<?php

declare(strict_types=1);

$base = $argv[1] ?? 'HEAD~1';
$root = dirname(__DIR__);
$command = sprintf('git -C %s diff --name-only %s -- tests/Visual/baselines', escapeshellarg($root), escapeshellarg($base));
$changed = array_values(array_filter(explode("\n", trim((string) shell_exec($command)))));

if ($changed === []) {
    fwrite(STDOUT, "No visual baseline changes.\n");
    exit(0);
}

if (getenv('VISUAL_BASELINE_REVIEWED') !== '1') {
    fwrite(STDERR, "Visual baseline changes require explicit review. Set VISUAL_BASELINE_REVIEWED=1 only after attaching and approving the diff artifacts.\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Reviewed %d visual baseline change(s).\n", count($changed)));
