<?php

declare(strict_types=1);

namespace App\Support\Themes;

use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use InvalidArgumentException;

final readonly class PublicThemeContext
{
    /**
     * @param  array<string, bool|int|float|string|null>  $config
     * @param  list<string>  $features
     */
    public function __construct(
        public PublicThemeId $identifier,
        public PublicLayoutType $layout,
        public array $config,
        public array $features,
    ) {
        if ($identifier->layout() !== $layout) {
            throw new InvalidArgumentException('The public theme identifier and layout must match.');
        }

        if (array_values(array_unique($features)) !== $features) {
            throw new InvalidArgumentException('Public theme features must be a unique ordered list.');
        }

        foreach ($features as $feature) {
            if (trim($feature) === '') {
                throw new InvalidArgumentException('Public theme features must be non-empty strings.');
            }
        }
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, $this->features, true);
    }

    public function layoutView(): string
    {
        return $this->layout->layoutView();
    }

    public function shellView(): string
    {
        return $this->layout->shellView();
    }

    /** @return array<string, bool|int|float|string|null> */
    public function __debugInfo(): array
    {
        return [
            'identifier' => $this->identifier->value,
            'layout' => $this->layout->value,
            'features' => implode(',', $this->features),
        ];
    }
}
