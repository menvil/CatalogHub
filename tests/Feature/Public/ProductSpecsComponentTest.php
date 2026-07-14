<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductSpecsComponentTest extends TestCase
{
    public function test_specs_render_sections_and_attributes_in_projection_order_using_display_values(): void
    {
        $html = Blade::render(
            "@include('public.components.product-specs', ['sections' => \$sections])",
            ['sections' => [
                ['label' => 'Display', 'attributes' => [
                    ['label' => 'Resolution', 'display_value' => '3840 × 2160', 'canonical_value' => 'raw-resolution'],
                    ['label' => 'Refresh rate', 'display_value' => '165 hertz'],
                ]],
                ['label' => 'Connectivity', 'attributes' => [
                    ['label' => 'Ports', 'display_value' => '2 × HDMI'],
                    ['label' => 'Legacy value', 'value' => 'Fallback value'],
                    ['label' => 'Unavailable'],
                ]],
                ['label' => 'Empty', 'attributes' => []],
            ]],
        );

        $this->assertStringContainsString('Display', $html);
        $this->assertStringContainsString('3840 × 2160', $html);
        $this->assertStringNotContainsString('raw-resolution', $html);
        $this->assertStringContainsString('Fallback value', $html);
        $this->assertStringContainsString('—', $html);
        $this->assertStringNotContainsString('Empty', $html);
        $this->assertLessThan(strpos($html, 'Connectivity'), strpos($html, 'Display'));
        $this->assertLessThan(strpos($html, 'Refresh rate'), strpos($html, 'Resolution'));
    }

    public function test_specs_component_is_safe_for_an_empty_payload(): void
    {
        $html = Blade::render("@include('public.components.product-specs', ['sections' => []])");

        $this->assertSame('', trim($html));
    }
}
