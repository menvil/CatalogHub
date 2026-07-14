<?php

namespace App\Domains\Themes\DTO;

final readonly class ThemeCompatibilityResult
{
    /**
     * @param  list<string>  $missingFeatures
     * @param  list<string>  $warnings
     */
    public function __construct(
        public bool $compatible,
        public array $missingFeatures = [],
        public array $warnings = [],
    ) {}
}
