<?php

declare(strict_types=1);

namespace Tests\Unit\Normalization;

use App\Support\Normalization\BrandInputNormalizer;
use PHPUnit\Framework\TestCase;

final class BrandInputNormalizerTest extends TestCase
{
    public function test_name_collapses_whitespace_without_changing_case_punctuation_or_unicode(): void
    {
        self::assertSame(
            'ASUS 华为 & Co.',
            BrandInputNormalizer::name("  ASUS \t 华为  &  Co.  "),
        );
        self::assertSame(
            'LG Electronics',
            BrandInputNormalizer::name("\u{00A0}\u{2003}LG\u{202F}\u{0085}Electronics\u{3000}"),
        );
    }

    public function test_nullable_url_trims_text_and_converts_blank_values_to_null(): void
    {
        self::assertSame('https://example.com/path/', BrandInputNormalizer::nullableUrl('  https://example.com/path/  '));
        self::assertNull(BrandInputNormalizer::nullableUrl('   '));
        self::assertNull(BrandInputNormalizer::nullableUrl(null));
    }

    public function test_country_code_is_trimmed_uppercased_and_nullable(): void
    {
        self::assertSame('KR', BrandInputNormalizer::countryCode(' kr '));
        self::assertNull(BrandInputNormalizer::countryCode('   '));
        self::assertNull(BrandInputNormalizer::countryCode(null));
    }
}
