<?php

namespace App\Domains\Projections\Builders;

use App\Data\Pricing\ProductPriceSummary;
use App\Domains\Projections\DTO\CategoryProjectionData;
use App\Domains\Projections\DTO\ProductProjectionData;
use App\Domains\Projections\DTO\SearchDocumentData;

final class SearchDocumentBuilder
{
    public function fromProductProjection(
        ProductProjectionData $projection,
        ?ProductPriceSummary $priceSummary = null,
    ): SearchDocumentData {
        $attributes = $this->attributesFromPayload($projection->payload);
        $filterValues = [
            'brand_id' => data_get($projection->payload, 'brand.id'),
            'brand_slug' => data_get($projection->payload, 'brand.slug'),
            'category_id' => data_get($projection->payload, 'category.id'),
        ];
        $sortValues = [
            'title' => $projection->title,
            'rating' => $this->ratingFromPayload($projection->payload),
            'price' => null,
        ];
        $searchParts = [
            $projection->title,
            data_get($projection->payload, 'product.model'),
            data_get($projection->payload, 'brand.name'),
            data_get($projection->payload, 'category.label')
                ?? data_get($projection->payload, 'category.name'),
        ];

        foreach ($attributes as $attribute) {
            $code = $attribute['code'] ?? null;

            if (! is_string($code) || $code === '') {
                continue;
            }

            if (($attribute['is_filterable'] ?? false) === true) {
                $filterValues[$code] = $attribute['canonical_value'] ?? null;
            }

            if (($attribute['is_sortable'] ?? false) === true) {
                $sortValues[$code] = $attribute['canonical_value'] ?? null;
            }

            if (($attribute['is_searchable'] ?? false) === true) {
                $searchParts[] = $attribute['display_value'] ?? $attribute['canonical_value'] ?? null;
            }
        }

        $filterValues = array_filter(
            $filterValues,
            fn (mixed $value): bool => $value !== null,
        );

        return $this->make(
            siteId: $projection->siteId,
            locale: $projection->locale,
            documentType: 'product',
            documentId: $projection->centralProductId,
            title: $projection->title,
            slug: $projection->slug,
            status: $projection->status->value,
            searchText: $this->searchText($searchParts),
            filterValues: $filterValues,
            sortValues: $sortValues,
            payload: $projection->payload,
            minPrice: $priceSummary?->minPrice,
            maxPrice: $priceSummary?->maxPrice,
        );
    }

    public function fromCategoryProjection(CategoryProjectionData $projection): SearchDocumentData
    {
        $children = data_get($projection->payload, 'children', []);
        $searchParts = [
            $projection->title,
            data_get($projection->payload, 'parent.title'),
        ];

        if (is_array($children)) {
            foreach ($children as $child) {
                if (is_array($child)) {
                    $searchParts[] = $child['title'] ?? null;
                }
            }
        }

        return $this->make(
            siteId: $projection->siteId,
            locale: $projection->locale,
            documentType: 'category',
            documentId: $projection->centralCategoryId,
            title: $projection->title,
            slug: $projection->slug,
            status: $projection->status->value,
            searchText: $this->searchText($searchParts),
            filterValues: array_filter([
                'category_id' => $projection->centralCategoryId,
                'parent_category_id' => $projection->parentCategoryId,
            ], fn (mixed $value): bool => $value !== null),
            sortValues: ['title' => $projection->title],
            payload: $projection->payload,
            minPrice: null,
            maxPrice: null,
        );
    }

    /**
     * @param  array<string, mixed>  $filterValues
     * @param  array<string, mixed>  $sortValues
     * @param  array<string, mixed>  $payload
     */
    private function make(
        int $siteId,
        string $locale,
        string $documentType,
        int $documentId,
        ?string $title,
        ?string $slug,
        string $status,
        string $searchText,
        array $filterValues,
        array $sortValues,
        array $payload,
        ?string $minPrice,
        ?string $maxPrice,
    ): SearchDocumentData {
        $checksum = hash('sha256', json_encode(
            compact(
                'siteId',
                'locale',
                'documentType',
                'documentId',
                'title',
                'slug',
                'status',
                'searchText',
                'filterValues',
                'sortValues',
                'payload',
                'minPrice',
                'maxPrice',
            ),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return new SearchDocumentData(
            siteId: $siteId,
            locale: $locale,
            documentType: $documentType,
            documentId: $documentId,
            title: $title,
            slug: $slug,
            status: $status,
            searchText: $searchText,
            filterValues: $filterValues,
            sortValues: $sortValues,
            payload: $payload,
            checksum: $checksum,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<array<string, mixed>>
     */
    private function attributesFromPayload(array $payload): array
    {
        $sections = $payload['spec_sections'] ?? [];
        $attributes = [];

        if (! is_array($sections)) {
            return [];
        }

        foreach ($sections as $section) {
            if (! is_array($section) || ! is_array($section['attributes'] ?? null)) {
                continue;
            }

            foreach ($section['attributes'] as $attribute) {
                if (is_array($attribute)) {
                    $attributes[] = $attribute;
                }
            }
        }

        return $attributes;
    }

    /**
     * @param  list<mixed>  $parts
     */
    private function searchText(array $parts): string
    {
        return collect($parts)
            ->filter(fn (mixed $value): bool => is_scalar($value) && trim((string) $value) !== '')
            ->map(fn (mixed $value): string => trim((string) $value))
            ->unique()
            ->implode(' ');
    }

    /** @param array<string, mixed> $payload */
    private function ratingFromPayload(array $payload): ?float
    {
        $rating = data_get($payload, 'rating.value', data_get($payload, 'rating'));

        return is_numeric($rating) ? (float) $rating : null;
    }
}
