<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Presentation\SafePresentationUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SafePresentationUrlTest extends TestCase
{
    #[DataProvider('safeUrlProvider')]
    public function test_safe_presentation_urls_are_whitelisted(string $url, bool $allowQuery, bool $allowFragment): void
    {
        self::assertTrue(SafePresentationUrl::allows($url, $allowQuery, $allowFragment));
    }

    /** @return iterable<string, array{string, bool, bool}> */
    public static function safeUrlProvider(): iterable
    {
        yield 'local path' => ['/admin/central', false, false];
        yield 'uppercase HTTPS' => ['HTTPS://example.test/path', false, false];
        yield 'query when enabled' => ['?mode=components', true, false];
        yield 'fragment when enabled' => ['#details', false, true];
    }

    #[DataProvider('unsafeUrlProvider')]
    public function test_unsafe_or_unapproved_presentation_urls_are_rejected(mixed $url): void
    {
        self::assertFalse(SafePresentationUrl::allows($url, allowQuery: true, allowFragment: true));
    }

    /** @return iterable<string, array{mixed}> */
    public static function unsafeUrlProvider(): iterable
    {
        yield 'backslash host bypass' => ['/\\evil.example/path'];
        yield 'protocol relative' => ['//evil.example/path'];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'empty' => ['   '];
        yield 'non-string' => [42];
    }
}
