<?php

declare(strict_types=1);

namespace App\Enums;

enum PublicLayoutType: string
{
    case MultiCategory = 'multi-category';
    case SingleCategory = 'single-category';

    public function layoutView(): string
    {
        return match ($this) {
            self::MultiCategory => 'layouts.public-multi-category',
            self::SingleCategory => 'layouts.public-single-category',
        };
    }

    public function shellView(): string
    {
        return match ($this) {
            self::MultiCategory => 'public.shells.multi-category',
            self::SingleCategory => 'public.shells.single-category',
        };
    }
}
