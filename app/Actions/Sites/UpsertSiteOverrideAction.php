<?php

namespace App\Actions\Sites;

use App\Models\Site;
use App\Models\SiteOverride;
use App\Services\Sites\AllowedSiteOverrideFields;
use Illuminate\Validation\ValidationException;

final class UpsertSiteOverrideAction
{
    public function __construct(private readonly AllowedSiteOverrideFields $allowed) {}

    public function handle(Site $site, string $entityType, int $entityId, string $field, ?string $localeCode, mixed $value, ?string $reason = null): SiteOverride
    {
        if (! $this->allowed->allowsEntityType($entityType)) {
            throw ValidationException::withMessages(['entity_type' => 'Unsupported override entity type.']);
        }
        if (! $this->allowed->allows($field)) {
            throw ValidationException::withMessages(['field' => 'Only whitelisted presentation fields can be overridden.']);
        }

        return $site->overrides()->updateOrCreate(['entity_type' => $entityType, 'entity_id' => $entityId, 'field' => $field, 'locale_code' => $localeCode], ['value_json' => ['value' => $value], 'reason' => $reason, 'status' => 'active']);
    }
}
