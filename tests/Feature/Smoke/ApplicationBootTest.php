<?php

declare(strict_types=1);

namespace Tests\Feature\Smoke;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

final class ApplicationBootTest extends TestCase
{
    public function test_documented_runtime_can_boot_the_application(): void
    {
        self::assertGreaterThanOrEqual(80500, PHP_VERSION_ID);
        self::assertSame(0, Artisan::call('about'));

        $this->get('/')
            ->assertOk()
            ->assertViewIs('pages.home');
    }
}
