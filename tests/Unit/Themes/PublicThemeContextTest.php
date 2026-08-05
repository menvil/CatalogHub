<?php

declare(strict_types=1);

namespace Tests\Unit\Themes;

use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use App\Support\Themes\PublicThemeContext;
use Error;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PublicThemeContextTest extends TestCase
{
    public function test_multi_and_single_theme_contracts_resolve_only_whitelisted_layouts(): void
    {
        $multi = new PublicThemeContext(
            PublicThemeId::MultiCategory,
            PublicLayoutType::MultiCategory,
            ['header_variant' => 'catalog'],
            ['categories', 'locale-selector'],
        );
        $single = new PublicThemeContext(
            PublicThemeId::SingleCategory,
            PublicLayoutType::SingleCategory,
            ['header_variant' => 'focused'],
            ['filters', 'locale-selector'],
        );

        self::assertSame('public.shells.multi-category', $multi->shellView());
        self::assertSame('layouts.public-multi-category', $multi->layoutView());
        self::assertTrue($multi->supports('categories'));
        self::assertSame('public.shells.single-category', $single->shellView());
        self::assertSame('layouts.public-single-category', $single->layoutView());
        self::assertTrue($single->supports('filters'));
    }

    public function test_unknown_theme_identifier_is_rejected_with_a_controlled_error(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown public theme identifier [uploaded/theme.blade.php].');

        PublicThemeId::parse('uploaded/theme.blade.php');
    }

    public function test_runtime_config_cannot_be_mutated_after_construction(): void
    {
        $context = new PublicThemeContext(
            PublicThemeId::MultiCategory,
            PublicLayoutType::MultiCategory,
            ['header_variant' => 'catalog'],
            ['locale-selector'],
        );

        $this->expectException(Error::class);

        $context->config['header_variant'] = 'unsafe';
    }
}
