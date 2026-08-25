<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class BrandFormVisualTest extends TestCase
{
    public function test_ca_013_references_have_reviewed_checksums_and_manifest_entries(): void
    {
        $root = dirname(__DIR__, 2);
        $names = [
            'ca-013__create__1440x1000',
            'ca-013__create__390x844',
            'ca-013__edit__1440x1000',
            'ca-013__edit__390x844',
        ];

        foreach ($names as $name) {
            $reference = "{$root}/tests/Visual/baselines/{$name}.png";
            $checksum = $reference.'.sha256';

            self::assertFileExists($reference);
            self::assertFileExists($checksum);
            self::assertSame(trim((string) file_get_contents($checksum)), hash_file('sha256', $reference));
        }

        $manifest = json_decode(
            (string) file_get_contents("{$root}/docs/ui/visual-references.json"),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($manifest);
        self::assertArrayHasKey('references', $manifest);
        self::assertIsArray($manifest['references']);
        self::assertIsList($manifest['references']);
        foreach ($manifest['references'] as $reference) {
            self::assertIsArray($reference);
        }

        $references = array_values(array_filter(
            $manifest['references'],
            static fn (array $reference): bool => $reference['screen_id'] === 'CA-013',
        ));

        self::assertCount(4, $references);
        $stateViewports = array_map(
            static fn (array $reference): string => $reference['state'].'@'.$reference['viewport'],
            $references,
        );
        sort($stateViewports);
        self::assertSame([
            'create@1440x1000',
            'create@390x844',
            'edit@1440x1000',
            'edit@390x844',
        ], $stateViewports);
        self::assertSame(array_fill(0, 4, 'brand-form-v2'), array_column($references, 'fixture'));
    }
}
