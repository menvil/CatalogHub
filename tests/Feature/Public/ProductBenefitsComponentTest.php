<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductBenefitsComponentTest extends TestCase
{
    public function test_benefits_block_renders_projection_benefits(): void
    {
        $html = Blade::render(
            "@include('public.components.product-benefits', ['benefits' => \$benefits])",
            ['benefits' => [
                'Sharp text at high pixel density',
                ['title' => 'Fast motion', 'description' => 'A smooth 165 Hz panel.'],
            ]],
        );

        $this->assertStringContainsString('Why it stands out', $html);
        $this->assertStringContainsString('Sharp text at high pixel density', $html);
        $this->assertStringContainsString('Fast motion', $html);
        $this->assertStringContainsString('A smooth 165 Hz panel.', $html);
    }

    public function test_benefits_block_hides_for_missing_or_empty_payload(): void
    {
        $this->assertSame('', trim(Blade::render("@include('public.components.product-benefits', ['benefits' => []])")));
        $this->assertSame('', trim(Blade::render("@include('public.components.product-benefits', ['benefits' => null])")));
    }
}
