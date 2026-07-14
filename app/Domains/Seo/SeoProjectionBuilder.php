<?php

namespace App\Domains\Seo;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Services\Sites\SiteOverrideResolver;
use App\Services\Translations\TranslationResolver;

final class SeoProjectionBuilder
{
    public function __construct(
        private readonly TranslationResolver $translationResolver,
        private readonly SiteOverrideResolver $siteOverrideResolver,
    ) {}

    /**
     * @param  array<string, mixed>  $media
     * @return array<string, mixed>
     */
    public function forProduct(
        Site $site,
        CentralProduct $product,
        string $locale,
        string $title,
        string $slug,
        bool $indexable,
        array $media,
    ): array {
        $translatedTitle = $this->translatedString($product, 'seo_title', $locale) ?? $title;
        $translatedDescription = $this->translatedString($product, 'seo_description', $locale)
            ?? $this->translatedString($product, 'short_description', $locale)
            ?? $title;
        $metaTitle = $this->overrideString(
            $site,
            $product,
            'meta_title',
            $locale,
            $translatedTitle,
        );
        $metaDescription = $this->overrideString(
            $site,
            $product,
            'meta_description',
            $locale,
            $translatedDescription,
        );

        return [
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription,
            'h1' => $title,
            'canonical_url' => $this->baseUrl($site).'/products/'.rawurlencode($slug),
            'robots' => $indexable ? 'index,follow' : 'noindex,nofollow',
            'og_title' => $metaTitle,
            'og_description' => $metaDescription,
            'og_image' => $this->resolvedOgImage($media),
            'hreflang' => [],
        ];
    }

    private function translatedString(CentralProduct $product, string $field, string $locale): ?string
    {
        $value = $this->translationResolver->resolve($product, $field, $locale)->value;

        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    private function overrideString(
        Site $site,
        CentralProduct $product,
        string $field,
        string $locale,
        string $fallback,
    ): string {
        $value = $this->siteOverrideResolver->resolve(
            $site,
            'product',
            (int) $product->getKey(),
            $field,
            $locale,
            fallbackValue: $fallback,
        );

        return is_scalar($value) && (string) $value !== '' ? (string) $value : $fallback;
    }

    private function baseUrl(Site $site): string
    {
        $domain = trim((string) $site->getAttribute('domain'));
        $baseUrl = $domain !== '' ? $domain : (string) config('app.url');

        if (! str_starts_with($baseUrl, 'http://') && ! str_starts_with($baseUrl, 'https://')) {
            $baseUrl = 'https://'.$baseUrl;
        }

        return rtrim($baseUrl, '/');
    }

    /**
     * @param  array<string, mixed>  $media
     */
    private function resolvedOgImage(array $media): ?string
    {
        foreach (['og', 'main'] as $role) {
            $item = $media[$role] ?? null;

            if (
                is_array($item)
                && ($item['is_placeholder'] ?? true) === false
                && is_string($item['url'] ?? null)
                && $item['url'] !== ''
            ) {
                return $item['url'];
            }
        }

        return null;
    }
}
