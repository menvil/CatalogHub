<?php

namespace Tests\Feature;

use Tests\TestCase;

class TestEnvironmentTest extends TestCase
{
    public function test_test_environment_is_isolated_from_production_services(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('cache.default'));
        $this->assertSame('sync', config('queue.default'));
        $this->assertSame('array', config('mail.default'));
    }
}
