<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;
use Tests\Support\BrandDetailFixture;

final class BrandDetailVisualTest extends TestCase
{
    public function test_ca_012_references_have_reviewed_checksums_and_manifest_entries(): void
    {
        $root = dirname(__DIR__, 2);
        $names = [
            'ca-012__active__1440x1000',
            'ca-012__active__390x844',
            'ca-012__archived__1440x1000',
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
        self::assertIsArray($manifest['references']);

        $references = array_values(array_filter(
            $manifest['references'],
            static fn (array $reference): bool => $reference['screen_id'] === 'CA-012',
        ));

        self::assertCount(3, $references);
        $stateViewports = array_map(
            static fn (array $reference): string => $reference['state'].'@'.$reference['viewport'],
            $references,
        );
        sort($stateViewports);
        self::assertSame([
            'active@1440x1000',
            'active@390x844',
            'archived@1440x1000',
        ], $stateViewports);
        self::assertSame(array_fill(0, 3, BrandDetailFixture::VERSION), array_column($references, 'fixture'));
    }
}
