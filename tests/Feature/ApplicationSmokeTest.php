<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ApplicationSmokeTest extends TestCase
{
    public function test_application_artisan_about_command_runs(): void
    {
        $this->assertSame(0, Artisan::call('about'));
    }
}
