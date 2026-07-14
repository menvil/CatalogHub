<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class PublicLayoutTest extends TestCase
{
    public function test_public_layout_renders_document_shell_header_content_and_footer(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('public.layouts.app')

            @section('title', 'Demo catalogue')

            @section('content')
                <h1>Projection-powered catalogue</h1>
            @endsection
            BLADE);

        $this->assertStringContainsString('<title>Demo catalogue</title>', $html);
        $this->assertStringContainsString('data-public-header', $html);
        $this->assertStringContainsString('Projection-powered catalogue', $html);
        $this->assertStringContainsString('data-public-footer', $html);
        $this->assertStringContainsString('/build/assets/public-', $html);
    }
}
