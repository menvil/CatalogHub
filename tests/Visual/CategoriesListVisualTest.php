<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class CategoriesListVisualTest extends TestCase
{
    public function test_ca_016_has_approved_implementation_baselines_for_every_required_viewport_and_mobile_cards(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root.'/docs/ui/visual-references.json'), true, flags: JSON_THROW_ON_ERROR);
        $references = array_values(array_filter($manifest['references'], static fn (array $reference): bool => $reference['screen_id'] === 'CA-016'));

        self::assertSame(['1440x1000', '1024x900', '768x1024', '390x844', '390x844'], array_column($references, 'viewport'));
        self::assertSame(['default', 'default', 'default', 'default', 'cards'], array_column($references, 'state'));
        self::assertSame(array_fill(0, 5, 'categories-list-v1'), array_column($references, 'fixture'));
        foreach ($references as $reference) {
            $path = $root.'/'.$reference['path'];
            self::assertFileExists($path);
            self::assertFileExists($path.'.sha256');
            self::assertSame($reference['sha256'], hash_file('sha256', $path));
            self::assertSame($reference['sha256'], trim((string) file_get_contents($path.'.sha256')));
            $dimensions = getimagesize($path);
            self::assertSame(array_map('intval', explode('x', $reference['viewport'])), array_slice($dimensions, 0, 2));
            self::assertSame(IMAGETYPE_PNG, $dimensions[2]);
            self::assertMatchesRegularExpression('/\A\d{4}-\d{2}-\d{2}\z/', $reference['approved_on']);
        }
        self::assertStringNotContainsString('pending-product-owner-review', (string) file_get_contents($root.'/docs/ui/screens/CA-016-categories-list.md'));
    }

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
