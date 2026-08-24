<?php

namespace App\Services\Media;

final class MediaVariantSpecificationRegistry
{
    /** @return list<MediaVariantSpecification> */
    public function all(): array
    {
        return array_map(fn (string $name, array $spec): MediaVariantSpecification => new MediaVariantSpecification($name, (int) $spec['width'], (int) $spec['height'], (string) $spec['fit'], (string) $spec['format'], (int) $spec['quality'], (bool) ($spec['allow_upscale'] ?? false)), array_keys(config('media.variants')), config('media.variants'));
    }

    /** @return list<MediaVariantSpecification> */
    public function forProfile(MediaVariantProfile $profile): array
    {
        $variants = array_filter(config('media.variants'), fn (array $variant): bool => ($variant['profile'] ?? MediaVariantProfile::Default->value) === $profile->value);

        return array_map(fn (string $name, array $spec): MediaVariantSpecification => new MediaVariantSpecification($name, (int) $spec['width'], (int) $spec['height'], (string) $spec['fit'], (string) $spec['format'], (int) $spec['quality'], (bool) ($spec['allow_upscale'] ?? false)), array_keys($variants), $variants);
    }

    public function get(string $name): MediaVariantSpecification
    {
        foreach ($this->all() as $spec) {
            if ($spec->name === $name) {
                return $spec;
            }
        } throw new \InvalidArgumentException("Unknown media variant [$name].");
    }
}
