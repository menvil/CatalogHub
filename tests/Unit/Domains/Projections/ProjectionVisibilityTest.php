<?php

namespace Tests\Unit\Domains\Projections;

use App\Domains\Projections\Support\ProjectionVisibility;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectionVisibilityTest extends TestCase
{
    /** @return iterable<string, array{mixed, bool}> */
    public static function visibilityValues(): iterable
    {
        yield 'boolean visible' => [true, true];
        yield 'boolean hidden' => [false, false];
        yield 'visible string' => ['visible', true];
        yield 'hidden string' => ['hidden', false];
        yield 'disabled string' => ['DISABLED', false];
        yield 'zero string' => ['0', false];
    }

    #[DataProvider('visibilityValues')]
    public function test_it_preserves_projection_visibility_semantics(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, ProjectionVisibility::isVisible($value));
    }
}
