<?php

namespace App\Domains\PublicSite;

use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use InvalidArgumentException;

final class LocalizedUrlResolver
{
    public function home(Site $site, string $locale): string
    {
        return $this->absolute($site, route('public.home', ['locale' => $locale], false));
    }

    public function category(Site $site, string $locale, SiteCategoryProjection|string $category): string
    {
        $slug = $this->resolveSlug($site, $locale, $category);

        return $this->absolute($site, route('public.categories.show', ['locale' => $locale, 'slug' => $slug], false));
    }

    public function listing(Site $site, string $locale, SiteCategoryProjection|string $category): string
    {
        $slug = $this->resolveSlug($site, $locale, $category);

        return $this->absolute($site, route('public.categories.products', ['locale' => $locale, 'slug' => $slug], false));
    }

    public function product(Site $site, string $locale, SiteProductProjection|string $product): string
    {
        $slug = $this->resolveSlug($site, $locale, $product);

        return $this->absolute($site, route('public.products.show', ['locale' => $locale, 'slug' => $slug], false));
    }

    /** @param list<string> $productSlugs */
    public function compare(Site $site, string $locale, array $productSlugs = []): string
    {
        $url = $this->absolute($site, route('public.compare', ['locale' => $locale], false));

        return $productSlugs === [] ? $url : $url.'?'.http_build_query(['products' => $productSlugs]);
    }

    public function article(Site $site, string $locale, string $slug): string
    {
        return $this->absolute($site, route('public.articles.show', ['locale' => $locale, 'slug' => $slug], false));
    }

    public function search(Site $site, string $locale): string
    {
        return $this->absolute($site, route('public.search', ['locale' => $locale], false));
    }

    private function projectionSlug(
        Site $site,
        string $locale,
        SiteCategoryProjection|SiteProductProjection $projection,
    ): string {
        if ((int) $projection->site_id !== (int) $site->getKey() || $projection->locale !== $locale) {
            throw new InvalidArgumentException('Projection does not belong to the requested site and locale context.');
        }

        return $projection->slug;
    }

    private function resolveSlug(
        Site $site,
        string $locale,
        SiteCategoryProjection|SiteProductProjection|string $resource,
    ): string {
        return is_string($resource) ? $resource : $this->projectionSlug($site, $locale, $resource);
    }

    private function absolute(Site $site, string $path): string
    {
        $siteDomain = trim(trim((string) $site->domain), '/');
        $configuredHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $domain = $siteDomain !== '' ? $siteDomain : (is_string($configuredHost) ? trim($configuredHost, '/') : '');

        if ($domain === '') {
            throw new InvalidArgumentException('Cannot generate a public URL without a domain.');
        }

        $scheme = data_get($site->settings_json, 'url_scheme', 'https');

        return rtrim((string) $scheme, ':/').'://'.$domain.'/'.ltrim($path, '/');
    }
}
