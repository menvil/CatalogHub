<?php

namespace Tests\Unit\Services;

use App\Services\Units\UnitConverter;
use ReflectionClass;
use Tests\TestCase;

class UnitConverterSkeletonTest extends TestCase
{
    public function test_unit_converter_resolves_from_container(): void
    {
        $converter = app(UnitConverter::class);
        $reflection = new ReflectionClass(UnitConverter::class);

        $this->assertInstanceOf(UnitConverter::class, $converter);
        $this->assertTrue($reflection->hasMethod('convert'));
        $this->assertTrue($reflection->hasMethod('toCanonical'));
        $this->assertTrue($reflection->hasMethod('fromCanonical'));
    }
}
