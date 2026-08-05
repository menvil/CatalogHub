<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use App\View\Components\Admin\PageHeader;
use Illuminate\Support\Facades\Blade;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PageHeaderTest extends TestCase
{
    public function test_page_header_without_actions_has_one_accessible_heading(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.page-header
                screen-id="CA-001"
                title="Central dashboard"
                description="Foundation shell state."
                :breadcrumbs="[['label' => 'Dashboard']]"
            />
            BLADE);

        $this->assertSame(1, substr_count($html, '<h1'));
        $this->assertStringContainsString('data-screen-id="CA-001"', $html);
        $this->assertStringContainsString('aria-label="Breadcrumbs"', $html);
        $this->assertStringNotContainsString('data-page-actions', $html);
    }

    public function test_page_header_supports_one_action_status_and_long_escaped_title(): void
    {
        $title = str_repeat('Long title ', 12).'<unsafe>';
        $html = Blade::render(<<<'BLADE'
            <x-admin.page-header
                screen-id="CA-TEST"
                :title="$title"
                status="Foundation"
                :breadcrumbs="[
                    ['label' => 'Dashboard', 'url' => '/admin/central'],
                    ['label' => 'Contract'],
                ]"
            >
                <x-slot:actions><a href="/safe-action">Safe action</a></x-slot:actions>
            </x-admin.page-header>
            BLADE, ['title' => $title]);

        $this->assertStringContainsString('data-page-actions', $html);
        $this->assertStringContainsString('Foundation', $html);
        $this->assertStringContainsString('&lt;unsafe&gt;', $html);
        $this->assertStringNotContainsString('<unsafe>', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    #[DataProvider('invalidScreenIdProvider')]
    public function test_page_header_rejects_malformed_screen_ids(string $screenId): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PageHeader(screenId: $screenId, title: 'Invalid contract');
    }

    /** @return array<string, array{string}> */
    public static function invalidScreenIdProvider(): array
    {
        return [
            'missing separator' => ['CA001'],
            'lowercase prefix' => ['ca-001'],
        ];
    }
}
