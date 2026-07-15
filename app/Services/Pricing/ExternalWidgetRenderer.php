<?php

namespace App\Services\Pricing;

use App\Data\Pricing\ExternalPriceWidgetData;
use App\Models\Site;
use App\Models\SiteProductProjection;

final class ExternalWidgetRenderer
{
    public function resolve(
        Site $site,
        SiteProductProjection $projection,
        bool $hasNormalizedOffers,
    ): ?ExternalPriceWidgetData {
        $mode = data_get($site->settings_json, 'pricing.provider_mode', 'normalized');
        $enabled = data_get($site->settings_json, 'pricing.external_widget.enabled', false);

        if (! is_string($mode) || ! in_array($mode, ['auto', 'widget'], true) || $enabled !== true) {
            return null;
        }

        if ($mode === 'auto' && $hasNormalizedOffers) {
            return null;
        }

        $provider = data_get($site->settings_json, 'pricing.external_widget.provider');
        $providers = config('pricing.external_widgets.providers', []);

        if (! is_string($provider) || ! is_array($providers)) {
            return null;
        }

        $providerConfig = $providers[$provider] ?? null;
        $baseUrl = is_array($providerConfig) ? ($providerConfig['base_url'] ?? null) : null;

        if (! is_string($baseUrl) || ! $this->isTrustedBaseUrl($baseUrl)) {
            return null;
        }

        $parameters = [
            'product_id' => (int) $projection->central_product_id,
            'site_code' => (string) $site->getAttribute('code'),
            'market_id' => (int) $site->market_id,
            'locale' => (string) $projection->getAttribute('locale'),
        ];
        $publisherId = data_get($site->settings_json, 'pricing.external_widget.publisher_id');

        if (is_string($publisherId) && preg_match('/\A[A-Za-z0-9_.-]{1,100}\z/', $publisherId) === 1) {
            $parameters['publisher_id'] = $publisherId;
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return new ExternalPriceWidgetData(
            provider: $provider,
            sourceUrl: $baseUrl.$separator.http_build_query($parameters, encoding_type: PHP_QUERY_RFC3986),
        );
    }

    private function isTrustedBaseUrl(string $url): bool
    {
        $parsed = parse_url($url);

        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && is_array($parsed)
            && strtolower((string) ($parsed['scheme'] ?? '')) === 'https'
            && is_string($parsed['host'] ?? null)
            && ! array_key_exists('user', $parsed)
            && ! array_key_exists('pass', $parsed)
            && ! array_key_exists('fragment', $parsed);
    }
}
