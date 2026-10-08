<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Data\CentralCatalog\CategoryActivityItem;
use App\Data\CentralCatalog\CategoryActivitySummary;
use App\Enums\AuditAction;
use App\Enums\CentralCategoryStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\Eloquent\Builder;

final class CategoryActivityQuery
{
    public const LIMIT = 12;

    /** Category-subject events only. Scope/global subjects never prove ownership. */
    private const LABELS = [
        'catalog.category.created' => 'Category created',
        'catalog.category.updated' => 'Category identity updated',
        'catalog.category.reparented' => 'Category reparented',
        'catalog.category.activated' => 'Category activated',
        'catalog.category.archived' => 'Category archived',
        'catalog.category.restored' => 'Category restored',
        'catalog.category.schema.reviewed' => 'Schema reviewed',
        'catalog.category.schema.approved' => 'Schema approved',
        'catalog.category.schema.archived' => 'Schema archived',
        'catalog.category.schema.restored' => 'Schema restored',
        'catalog.category.schema.invalidated' => 'Schema invalidated',
        'catalog.category.attribute.assigned' => 'Attribute assigned',
        'catalog.category.attribute.configured' => 'Assignment configured',
        'catalog.category.attribute.moved' => 'Assignment moved',
        'catalog.category.attribute.unassigned' => 'Attribute unassigned',
        'catalog.category.facet.created' => 'Facet created',
        'catalog.category.facet.updated' => 'Facet updated',
        'catalog.category.facet.removed' => 'Facet removed',
        'catalog.category.comparison.updated' => 'Comparison configured',
    ];

    public function forCategory(CentralCategory $category): CategoryActivitySummary
    {
        $query = $this->scoped($category)->with('actor:id,name');
        $recent = (clone $query)->orderByDesc('created_at')->orderByDesc('id')->limit(self::LIMIT)->get();
        $created = (clone $query)->where('action', AuditAction::CatalogCategoryCreated->value)->orderBy('created_at')->orderBy('id')->first();
        $updated = (clone $query)->where('action', AuditAction::CatalogCategoryUpdated->value)->orderByDesc('created_at')->orderByDesc('id')->first();

        return new CategoryActivitySummary(
            events: $recent->map(fn (AuditLogEntry $entry): CategoryActivityItem => new CategoryActivityItem(
                id: $entry->id, label: self::LABELS[$entry->action], actor: $this->actor($entry), at: $entry->created_at,
                summary: $this->summary($entry),
            ))->all(),
            createdBy: $created === null ? 'Not recorded' : $this->actor($created),
            lastIdentityUpdateBy: $updated === null ? 'Not recorded' : $this->actor($updated),
        );
    }

    /** @return Builder<AuditLogEntry> */
    private function scoped(CentralCategory $category): Builder
    {
        return AuditLogEntry::query()->select(['id', 'actor_id', 'action', 'after_json', 'created_at'])
            ->where('context', 'central')->whereNull('site_id')
            ->where('subject_type', $category->getMorphClass())->where('subject_id', (string) $category->id)
            ->whereIn('action', array_keys(self::LABELS));
    }

    private function actor(AuditLogEntry $entry): string
    {
        return $entry->actor_id === null ? 'Not recorded' : ($entry->actor->name ?? 'Actor unavailable');
    }

    private function summary(AuditLogEntry $entry): string
    {
        // No arbitrary audit JSON, translated bodies, emails or config are rendered.
        $after = $entry->after_json ?? [];
        if (in_array($entry->action, [AuditAction::CatalogCategoryActivated->value, AuditAction::CatalogCategoryArchived->value, AuditAction::CatalogCategoryRestored->value], true)) {
            $status = is_string($after['status'] ?? null) ? CentralCategoryStatus::tryFrom($after['status']) : null;

            return $status === null ? 'Category lifecycle changed.' : 'Category is '.$status->label().'.';
        }
        if (str_starts_with($entry->action, 'catalog.category.schema.') && is_int($after['schema_revision'] ?? null) && $after['schema_revision'] > 0) {
            return 'Schema revision '.$after['schema_revision'].'.';
        }

        return match ($entry->action) {
            AuditAction::CatalogCategoryCreated->value => 'Canonical Category registered.',
            AuditAction::CatalogCategoryUpdated->value => 'Source name or slug changed.',
            AuditAction::CatalogCategoryReparented->value => 'Category parent changed.',
            default => 'Category-local schema configuration changed.',
        };
    }
}
