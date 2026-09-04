<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class BrandPrototypeReferenceTest extends TestCase
{
    public function test_the_five_original_brand_prototypes_are_immutable_and_complete(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode(
            (string) file_get_contents($root.'/docs/ui/visual-references.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame(2, $manifest['version']);
        self::assertCount(5, $manifest['prototype_references']);
        self::assertSame(
            ['CA-011', 'CA-012', 'CA-013', 'CA-014', 'CA-015'],
            array_column($manifest['prototype_references'], 'screen_id'),
        );

        foreach ($manifest['prototype_references'] as $reference) {
            $path = $root.'/'.$reference['path'];

            self::assertFileExists($path);
            self::assertSame($reference['sha256'], hash_file('sha256', $path));
            self::assertSame([1448, 1086], array_slice(getimagesize($path) ?: [], 0, 2));
            self::assertSame('1448x1086', $reference['native_dimensions']);
            self::assertSame('1448x1086', $reference['intended_viewport']);
            self::assertSame('brand-prototype-v1', $reference['reference_version']);
            self::assertSame(basename($path), $reference['original_filename']);
        }
    }
}
