<?php

namespace App\Actions\Sites;

use App\Models\Site;
use App\Models\SiteOverride;
use App\Rules\UniqueSiteSlug;
use App\Services\Sites\AllowedSiteOverrideFields;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class UpsertSiteOverrideAction
{
    public function __construct(private readonly AllowedSiteOverrideFields $allowed) {}

    public function handle(Site $site, string $entityType, int $entityId, string $field, ?string $localeCode, mixed $value, ?string $reason = null): ?SiteOverride
    {
        if (! $this->allowed->allowsEntityType($entityType)) {
            throw ValidationException::withMessages(['entity_type' => 'Unsupported override entity type.']);
        }
        if (! $this->allowed->allows($field)) {
            throw ValidationException::withMessages(['field' => 'Only whitelisted presentation fields can be overridden.']);
        }

        if ($value === null || $value === '') {
            $site->overrides()->where(['entity_type' => $entityType, 'entity_id' => $entityId, 'field' => $field, 'locale_code' => $localeCode])->delete();

            return null;
        }

        if ($field === 'local_slug') {
            Validator::make(['slug' => $value], ['slug' => ['required', 'string', 'lowercase', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', new UniqueSiteSlug($site, $entityType, $localeCode, $entityId)]])->validate();
        }

        return $site->overrides()->updateOrCreate(['entity_type' => $entityType, 'entity_id' => $entityId, 'field' => $field, 'locale_code' => $localeCode], ['value_json' => ['value' => $value], 'reason' => $reason, 'status' => 'active']);
    }
}
