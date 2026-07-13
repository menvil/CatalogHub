<?php

namespace App\Domains\Themes\DTO;

final readonly class RenderedBlock
{
    /** @param array<string, mixed> $config */
    public function __construct(
        public string $code,
        public string $viewComponent,
        public array $config,
        public int $position,
    ) {}
}
