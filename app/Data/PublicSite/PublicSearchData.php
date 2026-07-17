<?php

namespace App\Data\PublicSite;

final readonly class PublicSearchData
{
    public function __construct(
        public string $term,
    ) {}
}
