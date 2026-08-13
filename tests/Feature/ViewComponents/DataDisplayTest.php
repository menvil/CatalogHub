<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Tests\TestCase;

final class DataDisplayTest extends TestCase
{
    public function test_status_identifier_and_reference_components_render_semantic_values(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.status-badge label="Active" tone="success" />
            <x-ui.identifier value="BR-0042" label="Brand code" />
            <x-ui.reference label="Tech Germany" kind="Site" url="/sites/tech-de" />
            <x-ui.reference label="" kind="User" />
        BLADE);

        $this->assertStringContainsString('data-ui-status="success"', $html);
        $this->assertStringContainsString('BR-0042', $html);
        $this->assertStringContainsString('aria-label="Brand code: BR-0042"', $html);
        $this->assertStringContainsString('items-center', $html);
        $this->assertStringContainsString('leading-none', $html);
        $this->assertStringContainsString('href="/sites/tech-de"', $html);
        $this->assertStringContainsString('Not available', $html);
    }

    public function test_timestamp_renders_absolute_value_timezone_and_optional_relative_hint(): void
    {
        $value = CarbonImmutable::parse('2026-08-05 10:15:00', 'UTC');
        $html = Blade::render(
            '<x-ui.timestamp :value="$value" timezone="Europe/Sofia" relative-hint="2 minutes ago" />',
            compact('value'),
        );

        $this->assertStringContainsString('datetime="2026-08-05T10:15:00+00:00"', $html);
        $this->assertStringContainsString('2026-08-05 13:15 EEST', $html);
        $this->assertStringContainsString('2 minutes ago', $html);
    }

    public function test_null_timestamp_is_graceful(): void
    {
        $html = Blade::render('<x-ui.timestamp :value="null" timezone="UTC" />');

        $this->assertStringContainsString('Not available', $html);
        $this->assertStringNotContainsString('<time', $html);
    }

    public function test_timestamp_rejects_non_string_timezones(): void
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Invalid timestamp timezone.');

        Blade::render('<x-ui.timestamp :value="null" :timezone="[]" />');
    }

    public function test_reference_rejects_browser_normalized_backslash_urls(): void
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('References require a safe URL.');

        Blade::render('<x-ui.reference label="Unsafe" kind="Site" :url="$url" />', ['url' => '/\\evil.example/path']);
    }
}
