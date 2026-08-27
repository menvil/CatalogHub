<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Imports\ExternalIdentityNormalizer;
use App\Support\Presentation\SafeExternalRecordUrl;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ExternalIdentityNormalizerTest extends TestCase
{
    public function test_external_ids_are_trimmed_but_remain_opaque_case_sensitive_values(): void
    {
        self::assertSame('000123', ExternalIdentityNormalizer::externalId('  000123  '));
        self::assertNotSame(
            ExternalIdentityNormalizer::hash('ABC'),
            ExternalIdentityNormalizer::hash('abc'),
        );
    }

    #[DataProvider('safeUrls')]
    public function test_external_record_urls_require_public_http_semantics(string $url, bool $expected): void
    {
        self::assertSame($expected, SafeExternalRecordUrl::allows($url));
    }

    public static function safeUrls(): iterable
    {
        yield ['https://example.test/brand/1', true];
        yield ['http://example.test/brand/1', true];
        yield ['javascript:alert(1)', false];
        yield ['data:text/html,test', false];
        yield ['file:///tmp/test', false];
        yield ['relative/path', false];
        yield ['https://user:pass@example.test/brand/1', false];
    }

    public function test_blank_external_id_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        ExternalIdentityNormalizer::externalId('   ');
    }

    #[DataProvider('externalIdsWithBoundaryControlCharacters')]
    public function test_control_characters_are_rejected_before_trimming(string $externalId): void
    {
        $this->expectException(ValidationException::class);
        ExternalIdentityNormalizer::externalId($externalId);
    }

    public static function externalIdsWithBoundaryControlCharacters(): iterable
    {
        yield 'leading NUL' => ["\0external"];
        yield 'trailing NUL' => ["external\0"];
        yield 'leading vertical tab' => ["\vexternal"];
        yield 'trailing vertical tab' => ["external\v"];
    }

    public function test_malformed_utf8_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        ExternalIdentityNormalizer::externalId("external\xC3\x28");
    }
}
