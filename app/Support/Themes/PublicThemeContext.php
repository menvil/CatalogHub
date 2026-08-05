<?php

declare(strict_types=1);

namespace App\Support\Themes;

use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use InvalidArgumentException;

final readonly class PublicThemeContext
{
    /**
     * @var array<string, bool|int|float|string|null>
     */
    public array $config;

    /** @var list<string> */
    public array $features;

    /**
     * @param  array<array-key, mixed>  $config
     * @param  array<array-key, mixed>  $features
     */
    public function __construct(
        public PublicThemeId $identifier,
        public PublicLayoutType $layout,
        array $config,
        array $features,
    ) {
        if ($identifier->layout() !== $layout) {
            throw new InvalidArgumentException('The public theme identifier and layout must match.');
        }

        if ($config !== [] && array_is_list($config)) {
            throw new InvalidArgumentException('Public theme config must be a keyed scalar map.');
        }

        $validatedConfig = [];

        foreach ($config as $key => $value) {
            if (! is_string($key) || (! is_scalar($value) && $value !== null)) {
                throw new InvalidArgumentException('Public theme config must be a keyed scalar map.');
            }

            $validatedConfig[$key] = $value;
        }

        if (! array_is_list($features)) {
            throw new InvalidArgumentException('Public theme features must be a list of non-empty strings.');
        }

        $validatedFeatures = [];

        foreach ($features as $feature) {
            if (! is_string($feature) || trim($feature) === '') {
                throw new InvalidArgumentException('Public theme features must be a list of non-empty strings.');
            }

            $validatedFeatures[] = $feature;
        }

        if (array_values(array_unique($validatedFeatures)) !== $validatedFeatures) {
            throw new InvalidArgumentException('Public theme features must be a unique ordered list.');
        }

        $this->config = $validatedConfig;
        $this->features = $validatedFeatures;
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
