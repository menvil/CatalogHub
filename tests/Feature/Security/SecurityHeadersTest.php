<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_it_adds_baseline_security_headers_to_web_responses(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
