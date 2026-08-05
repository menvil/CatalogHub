<?php

use App\Enums\Permission;
use App\Enums\UserRole;

return [
    'permissions' => Permission::values(),

    'roles' => [
        UserRole::SuperAdmin->value => ['*'],
        UserRole::CentralAdmin->value => [
            Permission::CentralPanelAccess->value,
            Permission::CentralPageAccess->value,
            Permission::CentralMutationExecute->value,
            Permission::CentralView->value,
            Permission::CentralManage->value,
            Permission::CatalogProductsManage->value,
            Permission::CatalogCategoriesManage->value,
            Permission::CatalogSchemaManage->value,
            Permission::ImportsManage->value,
            Permission::MediaManage->value,
            Permission::TranslationsManage->value,
            Permission::PricesManage->value,
            Permission::BackupsManage->value,
            Permission::CorrectionsReview->value,
        ],
        UserRole::CatalogEditor->value => [
            Permission::CentralPanelAccess->value,
            Permission::CentralPageAccess->value,
            Permission::CentralMutationExecute->value,
            Permission::CentralView->value,
            Permission::CatalogProductsManage->value,
            Permission::CatalogCategoriesManage->value,
            Permission::MediaManage->value,
        ],
        UserRole::SiteAdmin->value => [
            Permission::SitePanelAccess->value,
            Permission::SitePageAccess->value,
            Permission::SiteMutationExecute->value,
            Permission::SitesManage->value,
            Permission::SiteSettingsManage->value,
            Permission::SiteContentManage->value,
            Permission::TranslationsManage->value,
            Permission::ReviewsModerate->value,
            Permission::LeadsManage->value,
            Permission::CorrectionsRequest->value,
        ],
        UserRole::Translator->value => [
            Permission::CentralPanelAccess->value,
            Permission::CentralPageAccess->value,
            Permission::CentralMutationExecute->value,
            Permission::CentralView->value,
            Permission::SitePanelAccess->value,
            Permission::SitePageAccess->value,
            Permission::SiteMutationExecute->value,
            Permission::TranslationsManage->value,
        ],
        UserRole::Moderator->value => [
            Permission::SitePanelAccess->value,
            Permission::SitePageAccess->value,
            Permission::SiteMutationExecute->value,
            Permission::ReviewsModerate->value,
            Permission::LeadsManage->value,
        ],
    ],
];
