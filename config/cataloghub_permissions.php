<?php

use App\Enums\UserRole;

return [
    'permissions' => [
        'central.view',
        'central.manage',
        'catalog.products.manage',
        'catalog.categories.manage',
        'catalog.schema.manage',
        'imports.manage',
        'media.manage',
        'translations.manage',
        'sites.manage',
        'site.settings.manage',
        'site.content.manage',
        'reviews.moderate',
        'leads.manage',
        'prices.manage',
        'backups.manage',
    ],

    'roles' => [
        UserRole::SuperAdmin->value => ['*'],
        UserRole::CentralAdmin->value => [
            'central.view',
            'central.manage',
            'catalog.products.manage',
            'catalog.categories.manage',
            'catalog.schema.manage',
            'imports.manage',
            'media.manage',
            'translations.manage',
            'prices.manage',
            'backups.manage',
        ],
        UserRole::CatalogEditor->value => [
            'central.view',
            'catalog.products.manage',
            'catalog.categories.manage',
            'media.manage',
        ],
        UserRole::SiteAdmin->value => [
            'sites.manage',
            'site.settings.manage',
            'site.content.manage',
            'translations.manage',
            'reviews.moderate',
            'leads.manage',
        ],
        UserRole::Translator->value => [
            'translations.manage',
        ],
        UserRole::Moderator->value => [
            'reviews.moderate',
            'leads.manage',
        ],
    ],
];
