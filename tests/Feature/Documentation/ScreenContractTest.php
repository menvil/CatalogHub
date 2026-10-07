<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

final class ScreenContractTest extends TestCase
{
    public function test_all_foundation_screen_contracts_and_visual_references_validate(): void
    {
        $output = [];
        $exitCode = 0;

        exec(PHP_BINARY.' '.escapeshellarg(base_path('scripts/validate-screen-contracts.php')).' 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
    }

    public function test_validator_rejects_each_contract_and_manifest_boundary(): void
    {
        foreach ([
            'missing required field' => static function (array &$contracts, array &$references): void {
                unset($contracts[0]['purpose']);
            },
            'duplicate screen ID' => static function (array &$contracts, array &$references): void {
                $contracts[1]['screen_id'] = 'Z-001';
            },
            'invalid route' => static function (array &$contracts, array &$references): void {
                $contracts[0]['route'] = 'not-a-route';
            },
            'missing reference' => static function (array &$contracts, array &$references): void {
                array_pop($references);
            },
            'checksum mismatch' => static function (array &$contracts, array &$references): void {
                $references[0]['sha256'] = str_repeat('0', 64);
            },
        ] as $name => $mutate) {
            $result = $this->validateFixture($mutate);

            $this->assertNotSame(0, $result['exitCode'], $name.' was accepted.');
        }
    }

    public function test_pending_first_baseline_requires_explicit_review_state_and_matching_local_prototype(): void
    {
        $valid = $this->validateFixture(static function (): void {}, pendingPrototype: true);
        self::assertSame(0, $valid['exitCode'], $valid['output']);

        foreach ([
            'no pending review state' => static function (array &$contracts): void {
                unset($contracts[10]['visual_acceptance']);
            },
            'wrong reference version' => static function (array &$contracts): void {
                $contracts[10]['reference_version'] = 'unapproved-version';
            },
            'no prototype' => static function (array &$contracts, array &$references, array &$prototypes): void {
                $prototypes = [];
            },
            'no local source' => static function (array &$contracts, array &$references, array &$prototypes): void {
                $prototypes[0]['path'] = 'absent.png';
            },
            'changed source' => static function (array &$contracts, array &$references, array &$prototypes): void {
                $prototypes[0]['sha256'] = str_repeat('0', 64);
            },
        ] as $boundary => $mutate) {
            $result = $this->validateFixture($mutate, pendingPrototype: true);
            self::assertNotSame(0, $result['exitCode'], $boundary.' was accepted.');
        }
    }

    /** @return array{exitCode: int, output: string} */
    private function validateFixture(callable $mutate, bool $pendingPrototype = false): array
    {
        $root = sys_get_temp_dir().'/cataloghub-screen-contract-'.bin2hex(random_bytes(8));
        $files = new Filesystem;
        $contracts = [];
        $references = [];
        $prototypes = [];

        try {
            $files->ensureDirectoryExists($root.'/docs/ui/screens');
            $files->ensureDirectoryExists($root.'/tests/Visual/baselines');

            for ($number = 1; $number <= 10; $number++) {
                $id = sprintf('Z-%03d', $number);
                $contracts[] = [
                    'screen_id' => $id, 'context' => 'test', 'purpose' => 'test', 'roles' => 'test', 'route' => '/',
                    'viewports' => 'desktop=1x1', 'fixture' => 'test-v1', 'regions' => 'test', 'actions' => 'test',
                    'states' => 'test', 'permissions' => 'test', 'responsive' => 'test', 'out_of_scope' => 'test', 'reference_version' => 'v1',
                ];
                $path = 'tests/Visual/baselines/'.strtolower($id).'__default__1x1.png';
                file_put_contents($root.'/'.$path, 'fixture-'.$id);
                $references[] = ['screen_id' => $id, 'state' => 'default', 'viewport' => '1x1', 'fixture' => 'test-v1', 'path' => $path, 'sha256' => hash_file('sha256', $root.'/'.$path)];
            }

            if ($pendingPrototype) {
                $contracts[] = [...$contracts[0], 'screen_id' => 'CA-016', 'visual_acceptance' => 'pending-product-owner-review'];
                $path = 'prototype.png';
                file_put_contents($root.'/'.$path, 'immutable-approved-prototype');
                $prototypes[] = ['screen_id' => 'CA-016', 'reference_version' => 'v1', 'path' => $path, 'sha256' => hash_file('sha256', $root.'/'.$path)];
            }
            $mutate($contracts, $references, $prototypes);
            foreach ($contracts as $contract) {
                $frontMatter = implode("\n", array_map(static fn (string $key, string $value): string => "{$key}: {$value}", array_keys($contract), $contract));
                file_put_contents($root.'/docs/ui/screens/'.$contract['screen_id'].'.md', "---\n{$frontMatter}\n---\n");
            }
            file_put_contents($root.'/docs/ui/visual-references.json', json_encode(['references' => $references, 'prototype_references' => $prototypes], JSON_THROW_ON_ERROR));

            $output = [];
            $exitCode = 0;
            exec(PHP_BINARY.' '.escapeshellarg(base_path('scripts/validate-screen-contracts.php')).' '.escapeshellarg($root).' 2>&1', $output, $exitCode);

            return ['exitCode' => $exitCode, 'output' => implode(PHP_EOL, $output)];
        } finally {
            $files->deleteDirectory($root);
        }
    }
}
