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
    case CatalogBrandTagsUpdated = 'catalog.brand.tags.updated';
    case CatalogBrandOwnerAssigned = 'catalog.brand.owner.assigned';
    case CatalogBrandOwnerCleared = 'catalog.brand.owner.cleared';
    case CatalogBrandExternalIdentityLinked = 'catalog.brand.external_identity.linked';
    case CatalogBrandExternalIdentityUpdated = 'catalog.brand.external_identity.updated';
    case CatalogBrandExternalIdentityUnlinked = 'catalog.brand.external_identity.unlinked';
    case CatalogBrandActivated = 'catalog.brand.activated';
    case CatalogBrandArchived = 'catalog.brand.archived';
    case CatalogBrandRestored = 'catalog.brand.restored';
    case CatalogBrandLogoAssigned = 'catalog.brand.logo.assigned';
    case CatalogBrandLogoRemoved = 'catalog.brand.logo.removed';
    case CatalogBrandTranslationSaved = 'catalog.brand.translation.saved';
    case CatalogCategoryCreated = 'catalog.category.created';
    case CatalogCategoryUpdated = 'catalog.category.updated';
    case CatalogCategoryReparented = 'catalog.category.reparented';
    case CatalogCategoryReordered = 'catalog.category.reordered';
    case CatalogCategoryActivated = 'catalog.category.activated';
    case CatalogCategoryArchived = 'catalog.category.archived';
    case CatalogCategoryRestored = 'catalog.category.restored';
    case CatalogCategorySchemaReviewed = 'catalog.category.schema.reviewed';
    case CatalogCategorySchemaApproved = 'catalog.category.schema.approved';
    case CatalogCategorySchemaArchived = 'catalog.category.schema.archived';
    case CatalogCategorySchemaRestored = 'catalog.category.schema.restored';
    case CatalogCategorySchemaInvalidated = 'catalog.category.schema.invalidated';
    case CatalogAttributeOptionCreated = 'catalog.attribute.option.created';
    case CatalogAttributeOptionUpdated = 'catalog.attribute.option.updated';
    case CatalogCategoryFacetCreated = 'catalog.category.facet.created';
    case CatalogCategoryFacetUpdated = 'catalog.category.facet.updated';
    case CatalogCategoryFacetRemoved = 'catalog.category.facet.removed';
    case CatalogCategoryComparisonUpdated = 'catalog.category.comparison.updated';
    case CatalogAttributeIdentityReconciled = 'catalog.attribute.identity.reconciled';
    case CatalogAttributeCreated = 'catalog.attribute.created';
    case CatalogAttributeDisplayRuleConfigured = 'catalog.attribute.display_rule.configured';
    case CatalogAttributeUpdated = 'catalog.attribute.updated';
    case CatalogCategoryAttributeAssigned = 'catalog.category.attribute.assigned';
    case CatalogCategoryAttributeConfigured = 'catalog.category.attribute.configured';
    case CatalogCategoryAttributeMoved = 'catalog.category.attribute.moved';
    case CatalogCategoryAttributeUnassigned = 'catalog.category.attribute.unassigned';
    case TranslationApproved = 'translation.approved';
    case TranslationMarkedOutdated = 'translation.marked_outdated';
}
