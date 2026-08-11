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

    /** @return array{exitCode: int, output: string} */
    private function validateFixture(callable $mutate): array
    {
        $root = sys_get_temp_dir().'/cataloghub-screen-contract-'.bin2hex(random_bytes(8));
        $files = new Filesystem;
        $contracts = [];
        $references = [];

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

            $mutate($contracts, $references);
            foreach ($contracts as $contract) {
                $frontMatter = implode("\n", array_map(static fn (string $key, string $value): string => "{$key}: {$value}", array_keys($contract), $contract));
                file_put_contents($root.'/docs/ui/screens/'.$contract['screen_id'].'.md', "---\n{$frontMatter}\n---\n");
            }
            file_put_contents($root.'/docs/ui/visual-references.json', json_encode(['references' => $references], JSON_THROW_ON_ERROR));

            $output = [];
            $exitCode = 0;
            exec(PHP_BINARY.' '.escapeshellarg(base_path('scripts/validate-screen-contracts.php')).' '.escapeshellarg($root).' 2>&1', $output, $exitCode);

            return ['exitCode' => $exitCode, 'output' => implode(PHP_EOL, $output)];
        } finally {
            $files->deleteDirectory($root);
        }
    }
}
