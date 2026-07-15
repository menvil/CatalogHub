<?php

namespace App\Pricing\Adapters;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Data\Pricing\ExternalPriceOfferData;
use App\Data\Pricing\PriceSourceFetchResult;
use App\Enums\PriceSourceType;
use App\Models\PriceSource;
use App\Pricing\Adapters\Concerns\NormalizesExternalPriceOffers;
use App\Services\Pricing\OutboundPriceSourceUrlGuard;
use App\Services\Pricing\PriceSourceCredentialService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class GenericApiPriceAdapter implements PriceSourceAdapterInterface
{
    use NormalizesExternalPriceOffers;

    public function __construct(
        private readonly PriceSourceCredentialService $credentialService,
        private readonly OutboundPriceSourceUrlGuard $urlGuard,
    ) {}

    public function supports(PriceSource $source): bool
    {
        return $source->type === PriceSourceType::Api;
    }

    public function fetchOffers(PriceSource $source): PriceSourceFetchResult
    {
        $config = $source->config_json ?? [];
        $endpoint = $this->endpoint($config);
        $method = strtoupper((string) ($config['method'] ?? 'GET'));

        if ($method !== 'GET') {
            throw new InvalidArgumentException('Generic API price sources support GET requests only.');
        }

        $headers = $this->resolvedRequestMap($source, $config['headers'] ?? [], 'headers');
        $query = $this->resolvedRequestMap($source, $config['query'] ?? [], 'query');
        $allowedHosts = $config['allowed_hosts'] ?? [];

        if (! is_array($allowedHosts)) {
            throw new InvalidArgumentException('Generic API allowed_hosts must be an array.');
        }

        $httpResponse = Http::withOptions($this->urlGuard->requestOptions($endpoint, $allowedHosts))
            ->withHeaders($headers)
            ->get($endpoint, $query);

        if ($httpResponse->redirect()) {
            throw new InvalidArgumentException('Generic API redirects are not allowed.');
        }

        $response = $httpResponse->throw()->json();
        $items = filled($config['items_path'] ?? null)
            ? data_get($response, (string) $config['items_path'])
            : $response;

        if (! is_array($items) || ! array_is_list($items)) {
            throw new InvalidArgumentException('Generic API response items must be a JSON array.');
        }

        foreach ($items as $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Every generic API offer must be a JSON object.');
            }
        }

        /** @var list<array<string, mixed>> $items */
        return PriceSourceFetchResult::fromOffers($items, [
            'items_count' => count($items),
            'endpoint_host' => parse_url($endpoint, PHP_URL_HOST),
        ]);
    }

    public function normalizeOffer(PriceSource $source, array $rawPayload): ExternalPriceOfferData
    {
        $mapping = $source->config_json['field_mapping'] ?? [];

        if (! is_array($mapping)) {
            throw new InvalidArgumentException('Generic API field_mapping must be an object.');
        }

        $defaults = [
            'external_product_id' => 'external_product_id',
            'external_sku' => 'sku',
            'external_title' => 'title',
            'brand_name' => 'brand',
            'model_name' => 'model',
            'merchant_name' => 'merchant',
            'price' => 'price',
            'currency' => 'currency',
            'availability' => 'availability',
            'condition' => 'condition',
            'delivery_price' => 'delivery_price',
            'delivery_time' => 'delivery_time',
            'url' => 'url',
            'fetched_at' => 'fetched_at',
        ];
        $canonical = [];

        foreach ($defaults as $field => $defaultPath) {
            $path = $mapping[$field] ?? $defaultPath;

            if (! is_string($path) || $path === '') {
                throw new InvalidArgumentException("Generic API mapping for [{$field}] must be a field path.");
            }

            $value = data_get($rawPayload, $path);

            if ($value !== null && $value !== '') {
                $canonical[$field] = $value;
            }
        }

        return $this->normalizedOffer($source, [...$canonical, 'payload' => $rawPayload]);
    }

    /** @param array<string, mixed> $config */
    private function endpoint(array $config): string
    {
        $endpoint = trim((string) ($config['endpoint_url'] ?? ''));
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);

        if ($endpoint === '' || ! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Generic API endpoint_url must be an HTTP(S) URL.');
        }

        return $endpoint;
    }

    /**
     * @return array<string, string>
     */
    private function resolvedRequestMap(PriceSource $source, mixed $values, string $name): array
    {
        if (! is_array($values)) {
            throw new InvalidArgumentException("Generic API {$name} must be an object.");
        }

        $resolved = [];
        $credentials = null;

        foreach ($values as $key => $value) {
            if (! is_string($key) || ! is_scalar($value)) {
                throw new InvalidArgumentException("Generic API {$name} entries must be scalar.");
            }

            $value = (string) $value;

            if (str_starts_with($value, 'credential:')) {
                $credentials ??= $this->credentialService->resolve($source);
                $credentialKey = substr($value, strlen('credential:'));
                $value = data_get($credentials, $credentialKey);

                if (! is_scalar($value)) {
                    throw new InvalidArgumentException("Missing scalar API credential [{$credentialKey}].");
                }

                $value = (string) $value;
            }

            $resolved[$key] = $value;
        }

        return $resolved;
    }
}
