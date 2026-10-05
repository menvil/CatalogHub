<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\AuditLogEntry;
use App\Models\Site;
use App\Models\User;
use App\Support\Http\RequestId;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditRecorder
{
    /** @var list<string> */
    private const CATEGORY_SCHEMA_SNAPSHOT_FIELDS = ['category_id', 'name', 'slug', 'schema_status', 'schema_revision', 'schema_reviewed_revision', 'schema_approved_revision', 'reason', 'origin_type', 'origin_id'];

    /** @var list<string> */
    private const GLOBAL_ATTRIBUTE_FIELDS = ['definition_id', 'code', 'name', 'data_type', 'measurement_dimension_id', 'dimension', 'canonical_measurement_unit_id', 'canonical_unit', 'changed_fields', 'affected_category_count'];

    /** @var list<string> */
    private const ASSIGNMENT_FIELDS = ['assignment_id', 'category_id', 'definition_id', 'code', 'attribute_section_id', 'section_code', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable', 'changed_fields'];

    /** @var array<string, list<string>> */
    private const SNAPSHOT_FIELDS = [
        AuditAction::RoleAssigned->value => ['role'],
        AuditAction::MembershipChanged->value => ['role', 'is_active'],
        AuditAction::UserDisabled->value => ['is_disabled'],
        AuditAction::UserEnabled->value => ['is_disabled'],
        AuditAction::CatalogBrandCreated->value => ['name', 'slug', 'status', 'website_url', 'country_code', 'founded_year', 'support_url', 'contact_email', 'primary_color'],
        AuditAction::CatalogBrandUpdated->value => ['name', 'slug', 'website_url', 'country_code', 'founded_year', 'support_url', 'contact_email', 'primary_color'],
        AuditAction::CatalogBrandTagsUpdated->value => ['tags'],
        AuditAction::CatalogBrandOwnerAssigned->value => ['organization_id', 'organization_name'],
        AuditAction::CatalogBrandOwnerCleared->value => ['organization_id', 'organization_name'],
        AuditAction::CatalogBrandExternalIdentityLinked->value => ['source_code', 'external_id', 'external_url'],
        AuditAction::CatalogBrandExternalIdentityUpdated->value => ['source_code', 'external_id', 'external_url'],
        AuditAction::CatalogBrandExternalIdentityUnlinked->value => ['source_code', 'external_id', 'external_url'],
        AuditAction::CatalogBrandActivated->value => ['status'],
        AuditAction::CatalogBrandArchived->value => ['status'],
        AuditAction::CatalogBrandRestored->value => ['status'],
        AuditAction::CatalogBrandLogoAssigned->value => ['media_asset_id', 'role'],
        AuditAction::CatalogBrandLogoRemoved->value => ['media_asset_id', 'role'],
        AuditAction::CatalogBrandTranslationSaved->value => ['translation_id', 'locale', 'status', 'changed_fields'],
        AuditAction::CatalogCategoryCreated->value => ['category_id', 'name', 'slug', 'status', 'parent_id', 'parent_name', 'position', 'hierarchy_revision'],
        AuditAction::CatalogCategoryUpdated->value => ['name', 'slug', 'changed_fields'],
        AuditAction::CatalogCategoryReparented->value => ['parent_id', 'parent_name', 'position', 'hierarchy_revision', 'old_scope_key', 'old_scope_revision', 'old_ordered_ids', 'ordered_ids'],
        AuditAction::CatalogCategoryReordered->value => ['parent_id', 'parent_name', 'ordered_ids', 'hierarchy_revision'],
        AuditAction::CatalogCategoryActivated->value => ['status'],
        AuditAction::CatalogCategoryArchived->value => ['status'],
        AuditAction::CatalogCategoryRestored->value => ['status'],
        AuditAction::CatalogCategorySchemaReviewed->value => self::CATEGORY_SCHEMA_SNAPSHOT_FIELDS,
        AuditAction::CatalogCategorySchemaApproved->value => self::CATEGORY_SCHEMA_SNAPSHOT_FIELDS,
        AuditAction::CatalogCategorySchemaArchived->value => self::CATEGORY_SCHEMA_SNAPSHOT_FIELDS,
        AuditAction::CatalogCategorySchemaRestored->value => self::CATEGORY_SCHEMA_SNAPSHOT_FIELDS,
        AuditAction::CatalogCategorySchemaInvalidated->value => self::CATEGORY_SCHEMA_SNAPSHOT_FIELDS,
        AuditAction::CatalogAttributeCreated->value => self::GLOBAL_ATTRIBUTE_FIELDS,
        AuditAction::CatalogAttributeUpdated->value => self::GLOBAL_ATTRIBUTE_FIELDS,
        AuditAction::CatalogCategoryAttributeAssigned->value => self::ASSIGNMENT_FIELDS,
        AuditAction::CatalogCategoryAttributeConfigured->value => self::ASSIGNMENT_FIELDS,
        AuditAction::CatalogCategoryAttributeMoved->value => self::ASSIGNMENT_FIELDS,
        AuditAction::CatalogCategoryAttributeUnassigned->value => self::ASSIGNMENT_FIELDS,
        AuditAction::TranslationApproved->value => ['translation_id', 'locale', 'status', 'changed_fields'],
        AuditAction::TranslationMarkedOutdated->value => ['translation_id', 'locale', 'status', 'changed_fields'],
    ];

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function record(
        AuditAction $action,
        AuditContext $context,
        ?User $actor = null,
        ?Model $subject = null,
        ?Site $site = null,
        ?array $before = null,
        ?array $after = null,
    ): AuditLogEntry {
        return AuditLogEntry::query()->create([
            'actor_id' => $actor?->getKey(),
            'context' => $context->value,
            'site_id' => $site?->getKey(),
            'action' => $action->value,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject !== null ? (string) $subject->getKey() : null,
            'before_json' => $this->snapshot($action, $before),
            'after_json' => $this->snapshot($action, $after),
            'request_id' => $this->requestId(),
        ]);
    }

    public function requestContext(): AuditContext
    {
        $request = $this->request();

        if (! $request instanceof Request) {
            return AuditContext::System;
        }

        if ($request->is('admin/site', 'admin/site/*')) {
            return AuditContext::Site;
        }

        if ($request->is('admin/central', 'admin/central/*')) {
            return AuditContext::Central;
        }

        return AuditContext::System;
    }

    public function requestSite(): ?Site
    {
        $site = $this->request()?->attributes->get('site_context');

        return $site instanceof Site ? $site : null;
    }

    /** @param array<string, mixed>|null $values */
    private function snapshot(AuditAction $action, ?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $allowed = array_flip(self::SNAPSHOT_FIELDS[$action->value] ?? []);
        $snapshot = array_intersect_key($values, $allowed);

        return $snapshot === [] ? null : $snapshot;
    }

    private function requestId(): ?string
    {
        $request = $this->request();

        return $request instanceof Request ? RequestId::resolve($request) : null;
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        return app(Request::class);
    }
}
