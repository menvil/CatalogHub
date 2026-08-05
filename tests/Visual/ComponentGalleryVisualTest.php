<?php

declare(strict_types=1);

namespace Tests\Visual;

use PHPUnit\Framework\TestCase;

final class ComponentGalleryVisualTest extends TestCase
{
    public function test_approved_gallery_reference_is_present_and_unchanged(): void
    {
        $root = dirname(__DIR__, 2);
        $reference = $root.'/tests/Visual/baselines/component-gallery-wide.png';
        $checksum = $reference.'.sha256';

        $this->assertFileExists($reference);
        $this->assertFileExists($checksum);
        $this->assertSame(trim((string) file_get_contents($checksum)), hash_file('sha256', $reference));
    }
}
