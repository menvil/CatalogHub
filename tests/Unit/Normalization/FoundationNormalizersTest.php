<?php

declare(strict_types=1);

namespace Tests\Unit\Normalization;

use App\Support\Normalization\CodeNormalizer;
use App\Support\Normalization\HostNormalizer;
use App\Support\Normalization\LocaleNormalizer;
use App\Support\Normalization\SlugNormalizer;
use InvalidArgumentException;
use Tests\TestCase;

final class FoundationNormalizersTest extends TestCase
{
    public function test_code_slug_and_locale_normalization_is_idempotent(): void
    {
        $code = CodeNormalizer::normalize('  TECH_DE  ');
        $slug = SlugNormalizer::normalize('  Tech--Monitors  ');
        $locale = LocaleNormalizer::normalize(' de_de ');
        $host = HostNormalizer::normalize('HTTPS://CatalogHub.TEST:443/path');

        $this->assertSame('tech-de', $code);
        $this->assertSame('tech-monitors', $slug);
        $this->assertSame('de-DE', $locale);
        $this->assertSame('cataloghub.test', $host);
        $this->assertSame($code, CodeNormalizer::normalize($code));
        $this->assertSame($slug, SlugNormalizer::normalize($slug));
        $this->assertSame($locale, LocaleNormalizer::normalize($locale));
        $this->assertSame($host, HostNormalizer::normalize($host));
    }

    public function test_normalizers_reject_empty_or_invalid_values_without_transliterating(): void
    {
        foreach ([
            static fn (): string => CodeNormalizer::normalize(''),
            static fn (): string => CodeNormalizer::normalize('Техника'),
            static fn (): string => SlugNormalizer::normalize(''),
            static fn (): string => SlugNormalizer::normalize('Café'),
            static fn (): string => LocaleNormalizer::normalize('english'),
            static fn (): string => LocaleNormalizer::normalize('en_USA'),
            static fn (): string => HostNormalizer::normalize('/relative/path'),
        ] as $normalize) {
            $this->assertInvalid($normalize);
        }
    }

    private function assertInvalid(callable $normalize): void
    {
        try {
            $normalize();
            $this->fail('Expected invalid normalization input to be rejected.');
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);
        }
    }
}
