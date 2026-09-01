<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Normalization;

use App\Support\Normalization\CatalogTagNormalizer;
use PHPUnit\Framework\TestCase;

final class CatalogTagNormalizerTest extends TestCase
{
    public function test_it_canonicalizes_unicode_whitespace_and_case_insensitive_identity(): void
    {
        self::assertSame('Premium Tag', CatalogTagNormalizer::name("  Premium\u{00A0} Tag  "));
        self::assertSame('électro', CatalogTagNormalizer::identity("E\u{0301}LECTRO"));
        self::assertSame(
            hash('sha256', 'premium'),
            CatalogTagNormalizer::identityHash('Premium'),
        );
    }

    public function test_it_rejects_malformed_utf8_instead_of_preserving_invalid_bytes(): void
    {
        $malformed = "Invalid\xC3\x28";

        self::assertSame('', CatalogTagNormalizer::name($malformed));
        self::assertSame('', CatalogTagNormalizer::identity($malformed));
    }
}
