<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class CategoryFormVisualTest extends TestCase
{
    public function test_ca_018_prototype_is_immutable_and_pending_implementation_review_is_explicit(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = json_decode((string) file_get_contents($root.'/docs/ui/visual-references.json'), true, flags: JSON_THROW_ON_ERROR);
        $references = array_values(array_filter($manifest['prototype_references'], static fn (array $reference): bool => $reference['screen_id'] === 'CA-018'));
        self::assertCount(1, $references);
        $reference = $references[0];
        self::assertSame('categories-schema-prototype-v1', $reference['reference_version']);
        self::assertSame('91008de57b8a2f0e8a59761d907606caa68aaa12ca7c942e16bb487e466268dd', $reference['sha256']);
        self::assertSame($reference['sha256'], hash_file('sha256', $root.'/'.$reference['path']));
        self::assertSame([1448, 1086], array_slice(getimagesize($root.'/'.$reference['path']), 0, 2));
        $contract = (string) file_get_contents($root.'/docs/ui/screens/CA-018-category-create-edit.md');
        self::assertStringContainsString('visual_acceptance: pending-product-owner-review', $contract);
        self::assertStringContainsString('fixture: category-create-edit-v1', $contract);
        // No implementation baseline is approved for CA-018 by candidate capture.
        self::assertSame([], array_values(array_filter($manifest['references'], static fn (array $reference): bool => $reference['screen_id'] === 'CA-018')));
        self::assertSame([], glob($root.'/tests/Visual/baselines/ca-018__*') ?: []);
    }
}
