<?php

namespace Tests\Feature\Configuration;

use Tests\TestCase;

class ErrorReportingConfigurationTest extends TestCase
{
    public function test_external_error_reporting_is_opt_in_and_disables_default_pii(): void
    {
        $this->assertFalse((bool) config('error_reporting.enabled'));
        $this->assertSame('log', config('error_reporting.driver'));
        $this->assertFalse((bool) config('error_reporting.send_default_pii'));
    }

    public function test_production_template_contains_only_safe_error_reporting_placeholders(): void
    {
        $template = file_get_contents(base_path('.env.production.example'));

        $this->assertIsString($template);
        $this->assertStringContainsString('ERROR_REPORTING_ENABLED=false', $template);
        $this->assertStringContainsString('ERROR_REPORTING_DRIVER=log', $template);
        $this->assertStringContainsString('ERROR_REPORTING_DSN=CHANGE_ME', $template);
        $this->assertStringNotContainsString('https://public@', $template);
    }
}
