<?php

namespace Tests\Unit\Support;

use App\Support\Database\LiteralLikePattern;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class LiteralLikePatternTest extends TestCase
{
    #[Test]
    #[DataProvider('patterns')]
    public function it_escapes_like_metacharacters_as_literals(string $value, string $expected): void
    {
        $this->assertSame($expected, LiteralLikePattern::containing($value));
    }

    /** @return iterable<string, array{string, string}> */
    public static function patterns(): iterable
    {
        yield 'plain' => ['monitor', '%monitor%'];
        yield 'percent' => ['100%', '%100!%%'];
        yield 'underscore' => ['model_name', '%model!_name%'];
        yield 'escape character' => ['wow!', '%wow!!%'];
    }
}
