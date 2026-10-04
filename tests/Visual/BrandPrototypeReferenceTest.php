<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class BrandPrototypeReferenceTest extends TestCase
{
    public function test_registered_original_prototypes_are_immutable_and_complete(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode(
            (string) file_get_contents($root.'/docs/ui/visual-references.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        self::assertSame(2, $manifest['version']);
        self::assertCount(16, $manifest['prototype_references']);
        self::assertSame(
            array_map(static fn (int $number): string => sprintf('CA-%03d', $number), range(11, 26)),
            array_column($manifest['prototype_references'], 'screen_id'),
        );

        foreach ($manifest['prototype_references'] as $reference) {
            $path = $root.'/'.$reference['path'];

            self::assertFileExists($path);
            self::assertSame($reference['sha256'], hash_file('sha256', $path));
            self::assertSame([1448, 1086], array_slice(getimagesize($path) ?: [], 0, 2));
            self::assertSame('1448x1086', $reference['native_dimensions']);
            self::assertSame('1448x1086', $reference['intended_viewport']);
            $isBrand = in_array($reference['screen_id'], ['CA-011', 'CA-012', 'CA-013', 'CA-014', 'CA-015'], true);
            self::assertSame($isBrand ? 'brand-prototype-v1' : 'categories-schema-prototype-v1', $reference['reference_version']);
            $directory = $isBrand ? '1.3. Brands' : '1.4. Categories : Schema';
            self::assertSame('pictures/1. Central Admin/'.$directory.'/'.$reference['original_filename'], $reference['path']);
            self::assertSame(basename($path), $reference['original_filename']);
        }
    }
}
