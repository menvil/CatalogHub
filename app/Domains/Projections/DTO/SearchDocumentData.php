<?php

namespace App\Domains\Projections\DTO;

final readonly class SearchDocumentData
{
    /**
     * @param  array<string, mixed>  $filterValues
     * @param  array<string, mixed>  $sortValues
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $siteId,
        public string $locale,
        public string $documentType,
        public int $documentId,
        public ?string $title,
        public ?string $slug,
        public string $status,
        public string $searchText,
        public array $filterValues,
        public array $sortValues,
        public array $payload,
        public string $checksum,
        public ?string $minPrice,
    ) {}
}
