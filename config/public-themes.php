<?php

declare(strict_types=1);

use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use App\Enums\SiteMode;

return [
    'mode_defaults' => [
        SiteMode::MultiCategory->value => PublicThemeId::MultiCategory->value,
        SiteMode::SingleCategory->value => PublicThemeId::SingleCategory->value,
    ],

    'themes' => [
        PublicThemeId::MultiCategory->value => [
            'layout' => PublicLayoutType::MultiCategory->value,
            'config' => ['header_variant' => 'catalog'],
            'features' => ['categories', 'search-slot', 'locale-selector'],
        ],
        PublicThemeId::SingleCategory->value => [
            'layout' => PublicLayoutType::SingleCategory->value,
            'config' => ['header_variant' => 'focused'],
            'features' => ['filters', 'search-slot', 'locale-selector'],
        ],
    ],
];
