<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class CategoriesListVisualTest extends TestCase
{
    public function test_approved_ca_016_prototype_remains_immutable_and_matches_registered_provenance(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root.'/docs/ui/visual-references.json'), true, flags: JSON_THROW_ON_ERROR);
        $prototypes = array_values(array_filter($manifest['prototype_references'], static fn (array $reference): bool => $reference['screen_id'] === 'CA-016'));
        self::assertCount(1, $prototypes);
        $reference = $prototypes[0];
        self::assertSame('categories-schema-prototype-v1', $reference['reference_version']);
        self::assertSame('c8e776138aa1356369fa2a48efb89f32540ac2d4234e4ee74a1406cba92094f2', $reference['sha256']);
        self::assertSame($reference['sha256'], hash_file('sha256', $root.'/'.$reference['path']));
        $dimensions = getimagesize($root.'/'.$reference['path']);
        self::assertSame([1448, 1086], array_slice($dimensions, 0, 2));
    }
}
