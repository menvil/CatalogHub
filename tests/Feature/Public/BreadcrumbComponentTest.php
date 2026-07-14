<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class BreadcrumbComponentTest extends TestCase
{
    public function test_breadcrumbs_render_localized_home_category_and_product_items(): void
    {
        $html = Blade::render(
            "@include('public.components.breadcrumbs', ['items' => \$items])",
            ['items' => [
                ['label' => 'Home', 'url' => 'https://catalog.test/en-US'],
                ['label' => 'Monitors', 'url' => 'https://catalog.test/en-US/categories/monitors'],
                ['label' => 'Aurora 27', 'url' => null],
            ]],
        );

        $this->assertStringContainsString('aria-label="Breadcrumb"', $html);
        $this->assertStringContainsString('href="https://catalog.test/en-US"', $html);
        $this->assertStringContainsString('href="https://catalog.test/en-US/categories/monitors"', $html);
        $this->assertStringContainsString('aria-current="page">Aurora 27', $html);
        $this->assertLessThan(strpos($html, 'Monitors'), strpos($html, 'Home'));
        $this->assertLessThan(strpos($html, 'Aurora 27'), strpos($html, 'Monitors'));
    }

    public function test_breadcrumbs_are_safe_for_empty_items(): void
    {
        $this->assertSame('', trim(Blade::render("@include('public.components.breadcrumbs', ['items' => []])")));
    }
}
