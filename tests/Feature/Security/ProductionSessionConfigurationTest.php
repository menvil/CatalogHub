<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

class ProductionSessionConfigurationTest extends TestCase
{
    public function test_production_template_contains_hardened_session_settings(): void
    {
        $template = file_get_contents(base_path('.env.production.example'));

        $this->assertIsString($template);
        $this->assertStringContainsString('SESSION_LIFETIME=60', $template);
        $this->assertStringContainsString('SESSION_SECURE_COOKIE=true', $template);
        $this->assertStringContainsString('SESSION_HTTP_ONLY=true', $template);
        $this->assertStringContainsString('SESSION_SAME_SITE=lax', $template);
        $this->assertStringContainsString('SESSION_PARTITIONED_COOKIE=false', $template);
    }

    public function test_session_configuration_keeps_safe_framework_defaults(): void
    {
        $this->assertTrue((bool) config('session.http_only'));
        $this->assertSame('lax', config('session.same_site'));
        $this->assertSame('json', config('session.serialization'));
    }
}
