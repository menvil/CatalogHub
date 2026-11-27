<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class CategoryDetailVisualTest extends TestCase
{
    public function test_ca_017_prototype_is_immutable_and_pending_implementation_review_is_explicit(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root.'/docs/ui/visual-references.json'), true, flags: JSON_THROW_ON_ERROR);
        $references = array_values(array_filter($manifest['prototype_references'], static fn (array $reference): bool => $reference['screen_id'] === 'CA-017'));
        self::assertCount(1, $references);
        $reference = $references[0];
        self::assertSame('categories-schema-prototype-v1', $reference['reference_version']);
        self::assertSame('afa4cd4539d19818effb5796ef4e6b1da23c0c41dcd36fe144d6e92ae51a20de', $reference['sha256']);
        self::assertSame($reference['sha256'], hash_file('sha256', $root.'/'.$reference['path']));
        self::assertSame([1448, 1086], array_slice(getimagesize($root.'/'.$reference['path']), 0, 2));
        self::assertStringContainsString('visual_acceptance: pending-product-owner-review', (string) file_get_contents($root.'/docs/ui/screens/CA-017-category-detail.md'));
        self::assertStringContainsString('fixture: category-detail-v1', (string) file_get_contents($root.'/docs/ui/screens/CA-017-category-detail.md'));
    }
}
