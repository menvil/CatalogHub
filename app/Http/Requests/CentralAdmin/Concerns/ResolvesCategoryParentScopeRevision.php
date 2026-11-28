<?php

declare(strict_types=1);

namespace App\Http\Requests\CentralAdmin\Concerns;

use Illuminate\Validation\ValidationException;
use JsonException;

trait ResolvesCategoryParentScopeRevision
{
    /** Select the submitted parent's page-load token when JavaScript is unavailable. */
    protected function resolveParentScopeRevision(string $field): void
    {
        $snapshot = $this->input('parent_scope_revisions');
        if ($snapshot === null) {
            return;
        }

        $revisions = null;
        if (is_string($snapshot) && strlen($snapshot) <= 1048576) {
            try {
                $revisions = json_decode($snapshot, true, 2, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                // Invalid snapshots fail validation below; never read a fresh revision here.
            }
        }

        $parent = $this->input('parent_id');
        $key = is_string($parent) || is_int($parent) ? (string) $parent : '';
        $revision = is_array($revisions) ? ($revisions[$key] ?? null) : null;
        if (! is_int($revision) || $revision < 0) {
            throw ValidationException::withMessages(['parent_id' => 'The parent scope snapshot is invalid. Reload before saving.']);
        }

        $this->merge([$field => $revision]);
    }
}
