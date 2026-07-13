<?php

namespace App\Domains\Themes\DTO;

use App\Models\LayoutTemplate;
use Illuminate\Support\Collection;

final readonly class RenderedTemplate
{
    /** @param Collection<int, RenderedBlock> $blocks */
    public function __construct(
        public string $pageType,
        public ?LayoutTemplate $layout,
        public Collection $blocks,
    ) {}
}
