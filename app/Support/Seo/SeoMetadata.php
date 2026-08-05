<?php

declare(strict_types=1);

namespace App\Support\Seo;

use App\Enums\SiteDomainType;
use App\Models\SiteDomain;
use App\Models\SiteLocale;
use App\Support\Sites\SiteRuntimeContext;
use InvalidArgumentException;

final readonly class SeoMetadata
{
    /**
     * @param  array<string, string>  $alternates
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,
        public array $alternates,
    ) {
        if (trim($this->title) === '' || trim($this->canonical) === '') {
            throw new InvalidArgumentException('SEO title and canonical URL are required.');
        }

    }

    public static function forHome(SiteRuntimeContext $context): self
    {
        $site = $context->site;
        $domain = $site->domains
            ->filter(static fn (SiteDomain $domain): bool => $domain->is_primary
                && $domain->is_active
                && $domain->type === SiteDomainType::Primary)
            ->sortBy('id')
            ->first();

        if (! $domain instanceof SiteDomain) {
            throw new InvalidArgumentException('Public SEO metadata requires an active primary site domain.');
        }

        $scheme = data_get($site->settings_json, 'url_scheme', 'https');

        if (! is_string($scheme) || ! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Public SEO URL scheme must be http or https.');
        }

        $baseUrl = $scheme.'://'.$domain->host;
        $enabledLocales = $site->locales
            ->filter(static fn (SiteLocale $locale): bool => $locale->is_enabled
                && ($locale->locale === null || $locale->locale->is_active))
            ->sortBy([['position', 'asc'], ['id', 'asc']])
            ->values();
        $localeCodes = $enabledLocales->pluck('locale_code')->all();

        foreach ($localeCodes as $localeCode) {
            if (! is_string($localeCode) || preg_match('/\A[a-z]{2}(?:-[A-Z]{2})?\z/D', $localeCode) !== 1) {
                throw new InvalidArgumentException("Public SEO locale [{$localeCode}] cannot be used in localized routes.");
            }
        }

        if (count($localeCodes) !== count(array_unique($localeCodes, SORT_STRING))) {
            throw new InvalidArgumentException('Public SEO alternate locale identifiers must be unique.');
        }

        $alternates = $enabledLocales
            ->mapWithKeys(static fn (SiteLocale $locale): array => [
                $locale->locale_code => $baseUrl.route('public.home', ['locale' => $locale->locale_code], false),
            ])
            ->all();

        $title = data_get($site->settings_json, 'seo.meta_title');
        $description = data_get($site->settings_json, 'seo.meta_description');

        return new self(
            title: is_string($title) && trim($title) !== '' ? $title : $site->name,
            description: is_string($description) && trim($description) !== '' ? $description : null,
            canonical: $baseUrl.route('public.home', ['locale' => $context->resolvedLocale], false),
            alternates: $alternates,
        );
    }
}
