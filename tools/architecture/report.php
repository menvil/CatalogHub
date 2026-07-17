<?php

declare(strict_types=1);

use CatalogHub\PHPStan\Architecture\ArchitectureDebtScanner;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$root = dirname(__DIR__, 2);
$options = getopt('', ['github-output:', 'json:']);

try {
    $report = (new ArchitectureDebtScanner($root))->scan();
    $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

    if (is_string($options['json'] ?? null)) {
        file_put_contents($options['json'], $json);
    }

    if (is_string($options['github-output'] ?? null)) {
        file_put_contents($options['github-output'], implode(PHP_EOL, [
            'active='.$report['active'],
            'actual='.$report['actual'],
            'inline='.$report['inline'],
            'baseline='.$report['baseline'],
            'unregistered='.count($report['unregistered']),
            'stale='.count($report['stale']),
            'expired='.count($report['expired']),
            '',
        ]), FILE_APPEND);
    }

    echo $json;
    exit($report['valid'] ? 0 : 1);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
