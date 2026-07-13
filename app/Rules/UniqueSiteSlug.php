<?php

namespace App\Rules;

use App\Models\Site;
use App\Models\SiteOverride;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class UniqueSiteSlug implements ValidationRule
{
    public function __construct(private readonly Site $site, private readonly string $entityType, private readonly string $localeCode, private readonly int $entityId) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = SiteOverride::query()
            ->where('site_id', $this->site->id)
            ->where('entity_type', $this->entityType)
            ->where('field', 'local_slug')
            ->where('entity_id', '!=', $this->entityId)
            ->where('value_json->value', $value)
            ->where('status', 'active');

        if ($this->localeCode !== '') {
            $query->whereIn('locale_code', ['', $this->localeCode]);
        }

        $exists = $query->exists();

        if ($exists) {
            $fail('This local slug is already used for the site, locale, and entity type.');
        }
    }
}
