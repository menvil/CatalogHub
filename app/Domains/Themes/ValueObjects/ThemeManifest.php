<?php

namespace App\Domains\Themes\ValueObjects;

use App\Exceptions\Themes\InvalidThemeManifestException;

final readonly class ThemeManifest
{
    /** @var list<string> */
    public const PAGE_TYPES = ['home', 'category', 'product', 'compare', 'article', 'search', 'lead', 'review'];

    /**
     * @param  list<string>  $supports
     * @param  array<string, string>  $layouts
     */
    private function __construct(
        public string $code,
        public string $name,
        public array $supports,
        public array $layouts,
        public ?string $version,
        public ?string $preview,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        $code = self::requiredString($data, 'code');
        $name = self::requiredString($data, 'name');
        $supports = $data['supports'] ?? [];
        $layouts = $data['layouts'] ?? null;

        if (! is_array($supports) || ! array_is_list($supports)) {
            throw InvalidThemeManifestException::because('Theme manifest supports must be a list.');
        }

        $normalizedSupports = [];
        foreach ($supports as $capability) {
            if (! is_string($capability) || trim($capability) === '') {
                throw InvalidThemeManifestException::because('Theme manifest supports must contain non-empty strings.');
            }

            $normalizedSupports[] = trim($capability);
        }

        if (! is_array($layouts) || $layouts === [] || array_is_list($layouts)) {
            throw InvalidThemeManifestException::because('Theme manifest layouts must be a non-empty page type map.');
        }

        $normalizedLayouts = [];
        foreach ($layouts as $pageType => $layoutCode) {
            if (! is_string($pageType) || ! in_array($pageType, self::PAGE_TYPES, true)) {
                throw InvalidThemeManifestException::because("Unknown theme layout page type: {$pageType}.");
            }
            if (! is_string($layoutCode) || trim($layoutCode) === '') {
                throw InvalidThemeManifestException::because("Theme layout for {$pageType} must be a non-empty string.");
            }

            $normalizedLayouts[$pageType] = trim($layoutCode);
        }

        return new self(
            $code,
            $name,
            array_values(array_unique($normalizedSupports)),
            $normalizedLayouts,
            self::nullableString($data, 'version'),
            self::nullableString($data, 'preview'),
        );
    }

    public function supports(string $featureOrBlock): bool
    {
        return in_array($featureOrBlock, $this->supports, true);
    }

    public function layoutFor(string $pageType): ?string
    {
        return $this->layouts[$pageType] ?? null;
    }

    /**
     * @return array{
     *     code: string,
     *     name: string,
     *     supports: list<string>,
     *     layouts: array<string, string>,
     *     version: string|null,
     *     preview: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'supports' => $this->supports,
            'layouts' => $this->layouts,
            'version' => $this->version,
            'preview' => $this->preview,
        ];
    }

    /** @param array<string, mixed> $data */
    private static function requiredString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw InvalidThemeManifestException::because("Theme manifest {$key} is required.");
        }

        return trim($value);
    }

    /** @param array<string, mixed> $data */
    private static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        if ($value === null) {
            return null;
        }
        if (! is_string($value) || trim($value) === '') {
            throw InvalidThemeManifestException::because("Theme manifest {$key} must be a non-empty string when provided.");
        }

        return trim($value);
    }
}
