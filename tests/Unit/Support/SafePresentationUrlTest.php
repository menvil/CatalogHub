<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Presentation\SafePresentationUrl;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SafePresentationUrlTest extends TestCase
{
    #[DataProvider('safeUrlProvider')]
    public function test_presentation_url_boundaries_are_enforced(string $url, bool $allowQuery, bool $allowFragment, bool $expected): void
    {
        self::assertSame($expected, SafePresentationUrl::allows($url, $allowQuery, $allowFragment));
    }

    /** @return iterable<string, array{string, bool, bool, bool}> */
    public static function safeUrlProvider(): iterable
    {
        yield 'local path' => ['/admin/central', false, false, true];
        yield 'uppercase HTTPS' => ['HTTPS://example.test/path', false, false, true];
        yield 'query when enabled' => ['?mode=components', true, false, true];
        yield 'query when disabled' => ['?mode=components', false, false, false];
        yield 'fragment when enabled' => ['#details', false, true, true];
        yield 'fragment when disabled' => ['#details', false, false, false];
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
