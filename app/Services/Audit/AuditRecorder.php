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
    /** @var array<string, list<string>> */
    private const SNAPSHOT_FIELDS = [
        AuditAction::RoleAssigned->value => ['role'],
        AuditAction::MembershipChanged->value => ['role', 'is_active'],
        AuditAction::UserDisabled->value => ['is_disabled'],
        AuditAction::UserEnabled->value => ['is_disabled'],
        AuditAction::CatalogBrandCreated->value => ['name', 'slug', 'status', 'website_url', 'country_code'],
        AuditAction::CatalogBrandUpdated->value => ['name', 'slug', 'website_url', 'country_code'],
        AuditAction::CatalogBrandActivated->value => ['status'],
        AuditAction::CatalogBrandArchived->value => ['status'],
        AuditAction::CatalogBrandRestored->value => ['status'],
        AuditAction::CatalogBrandLogoAssigned->value => ['media_asset_id'],
        AuditAction::CatalogBrandLogoRemoved->value => ['media_asset_id'],
        AuditAction::CatalogBrandTranslationSaved->value => ['translation_id', 'locale', 'status', 'changed_fields'],
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
