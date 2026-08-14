<?php

declare(strict_types=1);

$root = $argv[1] ?? dirname(__DIR__);
$required = ['screen_id', 'context', 'purpose', 'roles', 'route', 'viewports', 'fixture', 'regions', 'actions', 'states', 'permissions', 'responsive', 'out_of_scope', 'reference_version'];
$contracts = [
    ...(glob($root.'/docs/ui/screens/Z-*.md') ?: []),
    ...(glob($root.'/docs/ui/screens/CA-*.md') ?: []),
];
$errors = [];
$ids = [];

foreach ($contracts as $contract) {
    $contents = (string) file_get_contents($contract);

    if (preg_match('/\A---\R(?<frontMatter>.*?)\R---/s', $contents, $matches) !== 1) {
        $errors[] = "{$contract}: missing YAML-style front matter.";

        continue;
    }

    $fields = [];
    foreach (explode("\n", trim($matches['frontMatter'])) as $line) {
        if (preg_match('/^(?<key>[a-z_]+):\s*(?<value>.+)$/', trim($line), $field) === 1) {
            $fields[$field['key']] = trim($field['value'], " \t\"");
        }
    }

    foreach ($required as $field) {
        if (! isset($fields[$field]) || $fields[$field] === '' || str_contains(strtolower($fields[$field]), 'tbd')) {
            $errors[] = "{$contract}: required field [{$field}] is missing or TBD.";
        }
    }

    $id = $fields['screen_id'] ?? '';
    if (preg_match('/\A(?:Z-0(?:0[1-9]|10)|CA-[0-9]{3})\z/', $id) !== 1) {
        $errors[] = "{$contract}: invalid screen ID [{$id}].";
    } elseif (isset($ids[$id])) {
        $errors[] = "{$contract}: duplicate screen ID [{$id}].";
    } else {
        $ids[$id] = true;
    }

    if (isset($fields['route']) && ! str_starts_with($fields['route'], '/')) {
        $errors[] = "{$contract}: route must start with /.";
    }
}

$foundationIds = array_filter(array_keys($ids), static fn (string $id): bool => str_starts_with($id, 'Z-'));
if (count($foundationIds) !== 10) {
    $errors[] = 'Expected exactly ten Z-001 through Z-010 contracts.';
}

$manifestPath = $root.'/docs/ui/visual-references.json';
if (! is_file($manifestPath)) {
    $errors[] = 'Visual reference manifest is missing.';
} else {
    $manifest = json_decode((string) file_get_contents($manifestPath), true);
    if (! is_array($manifest) || ! is_array($manifest['references'] ?? null)) {
        $errors[] = 'Visual reference manifest is invalid JSON.';
    } else {
        $referenceIds = [];
        foreach ($manifest['references'] as $reference) {
            foreach (['screen_id', 'state', 'viewport', 'fixture', 'path', 'sha256'] as $field) {
                if (! is_string($reference[$field] ?? null) || $reference[$field] === '') {
                    $errors[] = 'Visual reference has a missing required field ['.$field.'].';
                }
            }

            $key = ($reference['screen_id'] ?? '').':'.($reference['state'] ?? '').':'.($reference['viewport'] ?? '');
            if (isset($referenceIds[$key])) {
                $errors[] = "Visual reference manifest has duplicate entry [{$key}].";
            }
            $referenceIds[$key] = true;
            $viewport = $reference['viewport'] ?? '';
            if (! is_string($viewport) || preg_match('/\A[1-9]\d*x[1-9]\d*\z/', $viewport) !== 1) {
                $errors[] = "Visual reference [{$key}] has an invalid viewport.";
            }

            $fixture = $reference['fixture'] ?? '';
            if (! is_string($fixture) || preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*-v[1-9]\d*\z/', $fixture) !== 1) {
                $errors[] = "Visual reference [{$key}] has an invalid deterministic fixture.";
            }

            $screenId = $reference['screen_id'] ?? '';
            $state = $reference['state'] ?? '';
            $expectedPath = is_string($screenId) && is_string($state) && is_string($viewport)
                ? 'tests/Visual/baselines/'.strtolower($screenId).'__'.$state.'__'.$viewport.'.png'
                : null;
            if (($reference['path'] ?? null) !== $expectedPath) {
                $errors[] = "Visual reference [{$key}] does not use the canonical screenshot path.";
            }

            $path = $root.'/'.($reference['path'] ?? '');
            if (! is_file($path)) {
                $errors[] = "Visual reference [{$key}] has no local file.";
            } elseif (($reference['sha256'] ?? '') !== hash_file('sha256', $path)) {
                $errors[] = "Visual reference [{$key}] checksum does not match.";
            }
        }

        foreach (array_keys($ids) as $id) {
            if (! array_filter($manifest['references'], static fn (array $reference): bool => ($reference['screen_id'] ?? null) === $id)) {
                $errors[] = "Screen [{$id}] has no local visual reference.";
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors).PHP_EOL);
    exit(1);
}

fwrite(STDOUT, sprintf("Validated %d screen contracts and visual references.\n", count($contracts)));
