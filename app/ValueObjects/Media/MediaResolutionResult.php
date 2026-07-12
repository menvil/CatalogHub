<?php

namespace App\ValueObjects\Media;

use App\Models\MediaAsset;
use App\Models\MediaAssignment;

final readonly class MediaResolutionResult
{
    /**
     * @param  list<string>  $fallbackChain
     */
    public function __construct(
        public ?MediaAsset $asset,
        public ?MediaAssignment $assignment,
        public array $fallbackChain,
        public string $matchedStep,
        public string $placeholderUrl,
    ) {}

    public function found(): bool
    {
        return $this->asset !== null;
    }
}
