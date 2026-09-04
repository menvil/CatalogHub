<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class BrandsListVisualTest extends TestCase
{
    public function test_ca_011_references_have_reviewed_checksums_and_manifest_entries(): void
    {
        $root = dirname(__DIR__, 2);
        $names = [
            'ca-011__default__1440x1000',
            'ca-011__default__1024x900',
            'ca-011__default__768x1024',
            'ca-011__default__390x844',
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
        $references = array_values(array_filter(
            $manifest['references'],
            static fn (array $reference): bool => $reference['screen_id'] === 'CA-011',
        ));

        self::assertCount(4, $references);
        self::assertSame(['1440x1000', '1024x900', '768x1024', '390x844'], array_column($references, 'viewport'));
        self::assertSame(['brands-list-v3', 'brands-list-v3', 'brands-list-v3', 'brands-list-v3'], array_column($references, 'fixture'));
    }
}
