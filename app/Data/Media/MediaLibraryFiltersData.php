<?php

namespace App\Data\Media;

final readonly class MediaLibraryFiltersData
{
    public function __construct(
        public ?string $status,
        public ?string $type,
        public ?string $search,
    ) {}
}
