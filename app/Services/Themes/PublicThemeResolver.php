<?php

declare(strict_types=1);

namespace App\Services\Themes;

use App\Contracts\Themes\PublicThemeResolver as PublicThemeResolverContract;
use App\Enums\PublicLayoutType;
use App\Enums\PublicThemeId;
use App\Support\Sites\SiteRuntimeContext;
use App\Support\Themes\PublicThemeContext;
use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;

final readonly class PublicThemeResolver implements PublicThemeResolverContract
{
    public function __construct(private Repository $config) {}

    public function resolve(SiteRuntimeContext $site): PublicThemeContext
    {
        $identifier = PublicThemeId::parse($this->identifierFor($site));
        $definition = $this->config->get("public-themes.themes.{$identifier->value}");

        if (! is_array($definition)) {
            throw new InvalidArgumentException("Public theme [{$identifier->value}] is not registered.");
        }

        $layoutValue = $definition['layout'] ?? null;
        $layout = is_string($layoutValue) ? PublicLayoutType::tryFrom($layoutValue) : null;

        if (! $layout instanceof PublicLayoutType) {
            throw new InvalidArgumentException("Public theme [{$identifier->value}] has an invalid layout.");
        }

        return new PublicThemeContext(
            $identifier,
            $layout,
            $this->scalarConfig($definition['config'] ?? []),
            $this->features($definition['features'] ?? []),
        );
    }

    private function identifierFor(SiteRuntimeContext $context): string
    {
        $configured = data_get($context->site->settings_json, 'public_theme_id');

        if ($configured !== null) {
            if (! is_string($configured) || trim($configured) === '') {
                throw new InvalidArgumentException('The configured public theme identifier must be a non-empty string.');
            }

            return trim($configured);
        }

        $fallback = $this->config->get("public-themes.mode_defaults.{$context->site->mode->value}");

        if (! is_string($fallback) || $fallback === '') {
            throw new InvalidArgumentException("No public theme is configured for site mode [{$context->site->mode->value}].");
        }

        return $fallback;
    }

    /** @return array<string, bool|int|float|string|null> */
    private function scalarConfig(mixed $config): array
    {
        if (! is_array($config) || ($config !== [] && array_is_list($config))) {
            throw new InvalidArgumentException('Public theme config must be a keyed scalar map.');
        }

        foreach ($config as $key => $value) {
            if (! is_string($key) || (! is_scalar($value) && $value !== null)) {
                throw new InvalidArgumentException('Public theme config must contain scalar values only.');
            }
        }

        /** @var array<string, bool|int|float|string|null> $config */
        return $config;
    }

    /** @return list<string> */
    private function features(mixed $features): array
    {
        if (! is_array($features) || ! array_is_list($features)) {
            throw new InvalidArgumentException('Public theme features must be a list.');
        }

        foreach ($features as $feature) {
            if (! is_string($feature)) {
                throw new InvalidArgumentException('Public theme features must contain strings only.');
            }
        }

        /** @var list<string> $features */
        return $features;
    }
}
