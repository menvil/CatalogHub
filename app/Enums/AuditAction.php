<?php

namespace App\Enums;

enum AuditAction: string
{
    case Login = 'security.login';
    case Logout = 'security.logout';
    case RoleAssigned = 'security.role.assigned';
    case MembershipChanged = 'security.membership.changed';
    case UserDisabled = 'security.user.disabled';
    case UserEnabled = 'security.user.enabled';
    case CatalogBrandCreated = 'catalog.brand.created';
    case CatalogBrandUpdated = 'catalog.brand.updated';
    case CatalogBrandActivated = 'catalog.brand.activated';
    case CatalogBrandArchived = 'catalog.brand.archived';
    case CatalogBrandRestored = 'catalog.brand.restored';
    case CatalogBrandLogoAssigned = 'catalog.brand.logo.assigned';
    case CatalogBrandLogoRemoved = 'catalog.brand.logo.removed';
    case CatalogBrandTranslationSaved = 'catalog.brand.translation.saved';
}
