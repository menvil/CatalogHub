<?php

namespace App\Actions\Sites;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteOverride;
use App\Rules\UniqueSiteSlug;
use App\Services\Sites\AllowedSiteOverrideFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class UpsertSiteOverrideAction
{
    public function handle(Site $site, string $entityType, int $entityId, string $field, ?string $localeCode, mixed $value, ?string $reason = null): ?SiteOverride
    {
        if (! AllowedSiteOverrideFields::allowsEntityType($entityType)) {
            throw ValidationException::withMessages(['entity_type' => 'Unsupported override entity type.']);
        }
        if (! AllowedSiteOverrideFields::allows($field)) {
            throw ValidationException::withMessages(['field' => 'Only whitelisted presentation fields can be overridden.']);
        }

        $localeCode ??= '';

        if ($value === null || $value === '') {
            $site->overrides()->where(['entity_type' => $entityType, 'entity_id' => $entityId, 'field' => $field, 'locale_code' => $localeCode])->delete();

            return null;
        }

        if (! $this->targetExists($entityType, $entityId)) {
            throw ValidationException::withMessages(['entity_id' => 'The selected override target does not exist.']);
        }

        if ($localeCode !== '' && ! DB::table('site_locales')
            ->where('site_id', $site->getKey())
            ->where('locale_code', $localeCode)
            ->where('is_enabled', true)
            ->exists()) {
            throw ValidationException::withMessages(['locale_code' => 'The selected locale must be enabled for the site.']);
        }

        if ($field === 'local_slug') {
            Validator::make(['slug' => $value], ['slug' => ['required', 'string', 'lowercase', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', new UniqueSiteSlug($site, $entityType, $localeCode, $entityId)]])->validate();
        }

        return $site->overrides()->updateOrCreate(['entity_type' => $entityType, 'entity_id' => $entityId, 'field' => $field, 'locale_code' => $localeCode], ['value_json' => ['value' => $value], 'reason' => $reason, 'status' => 'active']);
    }

    private function targetExists(string $entityType, int $entityId): bool
    {
        return match ($entityType) {
            'product' => CentralProduct::query()->whereKey($entityId)->exists(),
            'category' => CentralCategory::query()->whereKey($entityId)->exists(),
            'brand' => CentralBrand::query()->whereKey($entityId)->exists(),
            default => false,
        };
    }
}
