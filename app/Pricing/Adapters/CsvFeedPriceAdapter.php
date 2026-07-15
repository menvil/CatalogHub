<?php

namespace App\Pricing\Adapters;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Data\Pricing\ExternalPriceOfferData;
use App\Data\Pricing\PriceSourceFetchResult;
use App\Enums\PriceSourceType;
use App\Models\PriceSource;
use App\Pricing\Adapters\Concerns\NormalizesExternalPriceOffers;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

final class CsvFeedPriceAdapter implements PriceSourceAdapterInterface
{
    use NormalizesExternalPriceOffers;

    public function supports(PriceSource $source): bool
    {
        return $source->type === PriceSourceType::CsvFeed;
    }

    public function fetchOffers(PriceSource $source): PriceSourceFetchResult
    {
        $config = $source->config_json ?? [];

        if (array_key_exists('rows', $config)) {
            return PriceSourceFetchResult::fromOffers($this->validatedRows($config['rows']));
        }

        $content = $config['csv_content'] ?? null;

        if ($content === null && filled($config['feed_url'] ?? null)) {
            $content = Http::get((string) $config['feed_url'])->throw()->body();
        }

        if ($content === null || trim((string) $content) === '') {
            return PriceSourceFetchResult::empty();
        }

        return PriceSourceFetchResult::fromOffers($this->parseCsv((string) $content, $config));
    }

    public function normalizeOffer(PriceSource $source, array $rawPayload): ExternalPriceOfferData
    {
        $canonical = [
            'external_product_id' => $this->mappedValue($source, $rawPayload, 'external_product_id_column', 'external_product_id'),
            'external_sku' => $this->mappedValue($source, $rawPayload, 'sku_column', 'sku'),
            'external_title' => $this->mappedValue($source, $rawPayload, 'title_column', 'title'),
            'brand_name' => $this->mappedValue($source, $rawPayload, 'brand_column', 'brand'),
            'model_name' => $this->mappedValue($source, $rawPayload, 'model_column', 'model'),
            'merchant_name' => $this->mappedValue($source, $rawPayload, 'merchant_column', 'merchant'),
            'price' => $this->mappedValue($source, $rawPayload, 'price_column', 'price'),
            'currency' => $this->mappedValue($source, $rawPayload, 'currency_column', 'currency'),
            'availability' => $this->mappedValue($source, $rawPayload, 'availability_column', 'availability'),
            'condition' => $this->mappedValue($source, $rawPayload, 'condition_column', 'condition'),
            'delivery_price' => $this->mappedValue($source, $rawPayload, 'delivery_price_column', 'delivery_price'),
            'delivery_time' => $this->mappedValue($source, $rawPayload, 'delivery_time_column', 'delivery_time'),
            'url' => $this->mappedValue($source, $rawPayload, 'url_column', 'url'),
            'fetched_at' => $this->mappedValue($source, $rawPayload, 'fetched_at_column', 'fetched_at'),
        ];

        $canonical = array_filter($canonical, fn (mixed $value): bool => $value !== null && $value !== '');

        return $this->normalizedOffer($source, [...$canonical, 'payload' => $rawPayload]);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<array<array-key, mixed>>
     */
    private function parseCsv(string $content, array $config): array
    {
        $delimiter = (string) ($config['delimiter'] ?? ',');

        if (strlen($delimiter) !== 1) {
            throw new InvalidArgumentException('CSV delimiter must be one character.');
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        $rows = array_map(
            fn (string $line): array => str_getcsv($line, $delimiter, '"', '\\'),
            array_values(array_filter($lines, fn (string $line): bool => trim($line) !== '')),
        );

        if ($rows === []) {
            return [];
        }

        if (($config['has_header'] ?? true) !== true) {
            return $rows;
        }

        $header = array_shift($rows);
        $mapped = [];

        foreach ($rows as $row) {
            if (count($row) !== count($header)) {
                throw new InvalidArgumentException('CSV row column count does not match its header.');
            }

            /** @var array<string, mixed> $combined */
            $combined = array_combine($header, $row);
            $mapped[] = $combined;
        }

        return $mapped;
    }

    /** @return list<array<array-key, mixed>> */
    private function validatedRows(mixed $rows): array
    {
        if (! is_array($rows)) {
            throw new InvalidArgumentException('CSV feed rows must be an array.');
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                throw new InvalidArgumentException('Every CSV feed row must be an array.');
            }
        }

        return array_values($rows);
    }

    /** @param array<string|int, mixed> $payload */
    private function mappedValue(
        PriceSource $source,
        array $payload,
        string $configKey,
        string $defaultColumn,
    ): mixed {
        $column = $source->config_json[$configKey] ?? $defaultColumn;

        return is_string($column) || is_int($column)
            ? ($payload[$column] ?? null)
            : null;
    }
}
