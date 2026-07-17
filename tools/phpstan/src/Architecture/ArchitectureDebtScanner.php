<?php

declare(strict_types=1);

namespace CatalogHub\PHPStan\Architecture;

use DateTimeImmutable;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final readonly class ArchitectureDebtScanner
{
    /** @var list<string> */
    private const SCANNED_DIRECTORIES = ['app', 'config', 'database', 'routes', 'tests', 'tools'];

    /** @var list<string> */
    private const REGISTRY_KEYS = [
        'id',
        'source',
        'file',
        'identifier',
        'count',
        'reason',
        'owner',
        'target',
        'expiresOn',
    ];

    public function __construct(private string $root) {}

    /**
     * @return array{
     *     active: int,
     *     actual: int,
     *     inline: int,
     *     baseline: int,
     *     unregistered: list<string>,
     *     stale: list<string>,
     *     mismatched: list<string>,
     *     expired: list<string>,
     *     valid: bool
     * }
     */
    public function scan(): array
    {
        $registered = $this->registeredSuppressions();
        $actual = [...$this->inlineSuppressions(), ...$this->baselineSuppressions()];
        $registeredByKey = [];
        $actualByKey = [];
        $expired = [];

        foreach ($registered as $suppression) {
            $key = $this->key($suppression);

            if (isset($registeredByKey[$key])) {
                throw new RuntimeException("Duplicate architecture debt suppression [{$key}].");
            }

            $registeredByKey[$key] = $suppression;

            if (new DateTimeImmutable($suppression['expiresOn']) < new DateTimeImmutable('today')) {
                $expired[] = $suppression['id'];
            }
        }

        foreach ($actual as $suppression) {
            $key = $this->key($suppression);
            $actualByKey[$key] = ($actualByKey[$key] ?? 0) + $suppression['count'];
        }

        $unregistered = array_values(array_diff(array_keys($actualByKey), array_keys($registeredByKey)));
        $stale = array_values(array_diff(array_keys($registeredByKey), array_keys($actualByKey)));
        $mismatched = [];

        foreach ($registeredByKey as $key => $suppression) {
            if (isset($actualByKey[$key]) && $actualByKey[$key] !== $suppression['count']) {
                $mismatched[] = "{$key}: registered {$suppression['count']}, actual {$actualByKey[$key]}";
            }
        }

        $inline = array_sum(array_map(
            fn (array $suppression): int => $suppression['source'] === 'phpstan_inline' ? $suppression['count'] : 0,
            $actual,
        ));
        $baseline = array_sum(array_map(
            fn (array $suppression): int => $suppression['source'] === 'phpstan_baseline' ? $suppression['count'] : 0,
            $actual,
        ));

        return [
            'active' => array_sum(array_column($registered, 'count')),
            'actual' => array_sum(array_values($actualByKey)),
            'inline' => $inline,
            'baseline' => $baseline,
            'unregistered' => $unregistered,
            'stale' => $stale,
            'mismatched' => $mismatched,
            'expired' => $expired,
            'valid' => $unregistered === [] && $stale === [] && $mismatched === [] && $expired === [],
        ];
    }

    /** @return list<array{id: string, source: string, file: string, identifier: string, count: int, reason: string, owner: string, target: string, expiresOn: string}> */
    private function registeredSuppressions(): array
    {
        $path = $this->root.'/tools/architecture/debt.json';
        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read architecture debt registry [{$path}].");
        }

        $registry = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($registry) || ($registry['version'] ?? null) !== 1 || ! is_array($registry['suppressions'] ?? null)) {
            throw new RuntimeException('Architecture debt registry must contain version 1 and a suppressions array.');
        }

        $suppressions = [];

        foreach ($registry['suppressions'] as $suppression) {
            if (! is_array($suppression) || array_keys($suppression) !== self::REGISTRY_KEYS) {
                throw new RuntimeException('Architecture debt registry entries must use the exact documented schema.');
            }

            foreach (['id', 'file', 'identifier', 'reason', 'owner', 'target', 'expiresOn'] as $key) {
                if (! is_string($suppression[$key]) || trim($suppression[$key]) === '') {
                    throw new RuntimeException("Architecture debt field [{$key}] must be a non-empty string.");
                }
            }

            if (! in_array($suppression['source'], ['phpstan_inline', 'phpstan_baseline'], true)) {
                throw new RuntimeException('Architecture debt source must be phpstan_inline or phpstan_baseline.');
            }

            if (preg_match('/\A[A-Za-z][A-Za-z0-9_.-]*\z/', $suppression['identifier']) !== 1) {
                throw new RuntimeException('Architecture debt must name one exact PHPStan identifier.');
            }

            if (! is_int($suppression['count']) || $suppression['count'] < 1) {
                throw new RuntimeException('Architecture debt count must be a positive integer.');
            }

            if (! is_file($this->root.'/'.$suppression['file'])) {
                throw new RuntimeException("Architecture debt file [{$suppression['file']}] does not exist.");
            }

            if (DateTimeImmutable::createFromFormat('!Y-m-d', $suppression['expiresOn']) === false) {
                throw new RuntimeException('Architecture debt expiresOn must use YYYY-MM-DD.');
            }

            /** @var array{id: string, source: string, file: string, identifier: string, count: int, reason: string, owner: string, target: string, expiresOn: string} $suppression */
            $suppressions[] = $suppression;
        }

        return $suppressions;
    }

    /** @return list<array{source: string, file: string, identifier: string, count: int}> */
    private function inlineSuppressions(): array
    {
        $suppressions = [];

        foreach (self::SCANNED_DIRECTORIES as $directory) {
            $path = $this->root.'/'.$directory;

            if (! is_dir($path)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($files as $file) {
                if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                if (! is_string($contents)) {
                    continue;
                }

                foreach (token_get_all($contents) as $token) {
                    if (! is_array($token) || ! in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                        continue;
                    }

                    preg_match_all(
                        '/@phpstan-ignore(?:(-line|-next-line)\b|\s+([A-Za-z][A-Za-z0-9_.-]*))/',
                        $token[1],
                        $matches,
                    );

                    foreach ($matches[0] as $index => $_match) {
                        $suppressions[] = [
                            'source' => 'phpstan_inline',
                            'file' => $this->relativePath($file->getPathname()),
                            'identifier' => $matches[1][$index] !== '' ? '*' : $matches[2][$index],
                            'count' => 1,
                        ];
                    }
                }
            }
        }

        return $suppressions;
    }

    /** @return list<array{source: string, file: string, identifier: string, count: int}> */
    private function baselineSuppressions(): array
    {
        $path = $this->root.'/phpstan-baseline.neon';

        if (! is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException('Unable to read phpstan-baseline.neon.');
        }

        preg_match_all(
            '/identifier:\s*([^\s]+).*?count:\s*(\d+).*?path:\s*([^\r\n]+)/s',
            $contents,
            $matches,
            PREG_SET_ORDER,
        );
        $suppressions = [];

        foreach ($matches as $match) {
            $suppressions[] = [
                'source' => 'phpstan_baseline',
                'file' => trim($match[3], " \t'\""),
                'identifier' => trim($match[1], " \t'\""),
                'count' => (int) $match[2],
            ];
        }

        return $suppressions;
    }

    /** @param array{source: string, file: string, identifier: string} $suppression */
    private function key(array $suppression): string
    {
        return $suppression['source'].':'.$suppression['file'].':'.$suppression['identifier'];
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace($this->root, '', $path), DIRECTORY_SEPARATOR);
    }
}
