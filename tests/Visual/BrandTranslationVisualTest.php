<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;
use Tests\Support\BrandTranslationFixture;

final class BrandTranslationVisualTest extends TestCase
{
    public function test_ca_015_current_v1_references_have_checksums_and_manifest_entries(): void
    {
        $root = dirname(__DIR__, 2);
        $names = [
            'ca-015__missing__1440x1000',
            'ca-015__existing__1440x1000',
            'ca-015__existing__390x844',
        ];

        foreach ($names as $name) {
            $reference = "{$root}/tests/Visual/baselines/{$name}.png";
            self::assertFileExists($reference);
            self::assertFileExists($reference.'.sha256');
            self::assertSame(trim((string) file_get_contents($reference.'.sha256')), hash_file('sha256', $reference));
        }

        $manifest = json_decode((string) file_get_contents("{$root}/docs/ui/visual-references.json"), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);
        $references = array_values(array_filter(
            $manifest['references'],
            static fn (array $reference): bool => $reference['screen_id'] === 'CA-015',
        ));

        self::assertCount(3, $references);
        self::assertSame(array_fill(0, 3, BrandTranslationFixture::VERSION), array_column($references, 'fixture'));
    }
}
