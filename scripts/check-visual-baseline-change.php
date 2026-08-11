<?php

declare(strict_types=1);

$base = $argv[1] ?? 'HEAD~1';
$root = $argv[2] ?? dirname(__DIR__);
$command = sprintf('git -C %s diff --name-only %s -- tests/Visual/baselines', escapeshellarg($root), escapeshellarg($base));
$output = [];
$exitCode = 0;
exec($command, $output, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Unable to compare visual baselines against [{$base}].\n");
    exit($exitCode);
}

$changed = array_values(array_filter($output));

if ($changed === []) {
    fwrite(STDOUT, "No visual baseline changes.\n");
    exit(0);
}

if (getenv('VISUAL_BASELINE_REVIEWED') !== '1') {
    fwrite(STDERR, "Visual baseline changes require explicit review. Set VISUAL_BASELINE_REVIEWED=1 only after attaching and approving the diff artifacts.\n");
    exit(1);
}

fwrite(STDOUT, sprintf("Reviewed %d visual baseline change(s).\n", count($changed)));
