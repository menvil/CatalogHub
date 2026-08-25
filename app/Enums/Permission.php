<?php

namespace App\Enums;

enum Permission: string
{
    case CentralPanelAccess = 'central.panel.access';
    case CentralPageAccess = 'central.page.access';
    case CentralMutationExecute = 'central.mutation.execute';
    case SitePanelAccess = 'site.panel.access';
    case SitePageAccess = 'site.page.access';
    case SiteMutationExecute = 'site.mutation.execute';
    case CentralView = 'central.view';
    case CentralManage = 'central.manage';
    case CatalogProductsManage = 'catalog.products.manage';
    case CatalogBrandsManage = 'catalog.brands.manage';
    case CatalogCategoriesManage = 'catalog.categories.manage';
    case CatalogSchemaManage = 'catalog.schema.manage';
    case ImportsManage = 'imports.manage';
    case MediaManage = 'media.manage';
    case TranslationsManage = 'translations.manage';
    case SitesManage = 'sites.manage';
    case SiteSettingsManage = 'site.settings.manage';
    case SiteContentManage = 'site.content.manage';
    case ReviewsModerate = 'reviews.moderate';
    case LeadsManage = 'leads.manage';
    case PricesManage = 'prices.manage';
    case BackupsManage = 'backups.manage';
    case CorrectionsRequest = 'corrections.request';
    case CorrectionsReview = 'corrections.review';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }
}
