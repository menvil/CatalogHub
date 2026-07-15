<?php

namespace App\Data\Content;

final readonly class RelatedContentData
{
    public function __construct(
        public string $typeLabel,
        public string $title,
        public ?string $excerpt,
        public string $url,
        public ?string $publishedDate,
    ) {}
}
