<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;
use Tests\Support\BrandMediaFixture;

final class BrandMediaVisualTest extends TestCase
{
    public function test_ca_014_references_have_reviewed_checksums_and_manifest_entries(): void
    {
        $root = dirname(__DIR__, 2);
        $names = ['ca-014__empty__1440x1000', 'ca-014__logo-ready__1440x1000', 'ca-014__logo-ready__390x844'];

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
            static fn (array $reference): bool => $reference['screen_id'] === 'CA-014',
        ));

        self::assertCount(3, $references);
        $identitySet = [];
        foreach ($references as $reference) {
            $identitySet[$reference['state'].'@'.$reference['viewport']] = $reference['path'];
        }
        self::assertSame([
            'empty@1440x1000' => 'tests/Visual/baselines/ca-014__empty__1440x1000.png',
            'logo-ready@1440x1000' => 'tests/Visual/baselines/ca-014__logo-ready__1440x1000.png',
            'logo-ready@390x844' => 'tests/Visual/baselines/ca-014__logo-ready__390x844.png',
        ], $identitySet);
        self::assertSame(
            array_fill(0, 3, BrandMediaFixture::VERSION),
            array_column($references, 'fixture'),
        );
        foreach ($references as $reference) {
            self::assertSame(
                hash_file('sha256', "{$root}/{$reference['path']}"),
                $reference['sha256'],
            );
        }
    }
}
