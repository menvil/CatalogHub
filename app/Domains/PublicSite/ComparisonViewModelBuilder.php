<?php

namespace App\Domains\PublicSite;

use App\Models\SiteProductProjection;
use Illuminate\Support\Collection;

final class ComparisonViewModelBuilder
{
    /**
     * @param  Collection<int, SiteProductProjection>  $projections
     * @return array{
     *     products: list<array{title: string|null, slug: string, media: array<string, mixed>}>,
     *     sections: list<array{label: string, attributes: list<array{label: string, values: list<mixed>}>}>,
     *     error: string|null
     * }
     */
    public function build(Collection $projections): array
    {
        $projections = $projections->take(4)->values();
        $products = $projections->map(fn (SiteProductProjection $projection): array => [
            'title' => $projection->title,
            'slug' => $projection->slug,
            'media' => $projection->media_json ?? [],
        ])->all();

        if ($projections->count() < 2) {
            return ['products' => $products, 'sections' => [], 'error' => 'Select at least two available products to compare.'];
        }

        $categoryIds = $projections
            ->map(fn (SiteProductProjection $projection): mixed => data_get($projection->payload_json, 'category.id'))
            ->filter(fn (mixed $id): bool => $id !== null)
            ->unique()
            ->values();

        if ($categoryIds->count() !== 1) {
            return ['products' => $products, 'sections' => [], 'error' => 'Products must belong to the same category.'];
        }

        /** @var array<string, array{label: string, attributes: array<string, array{label: string, values: array<int, mixed>}>}> $sectionMap */
        $sectionMap = [];

        foreach ($projections as $productIndex => $projection) {
            $sections = data_get($projection->payload_json, 'spec_sections', []);
            if (! is_array($sections)) {
                continue;
            }

            foreach ($sections as $sectionIndex => $section) {
                if (! is_array($section)) {
                    continue;
                }
                $sectionKey = (string) ($section['code'] ?? $section['label'] ?? $sectionIndex);
                $sectionMap[$sectionKey] ??= [
                    'label' => (string) ($section['label'] ?? $section['code'] ?? 'Details'),
                    'attributes' => [],
                ];
                $attributes = $section['attributes'] ?? [];

                if (! is_array($attributes)) {
                    continue;
                }
                foreach ($attributes as $attributeIndex => $attribute) {
                    if (! is_array($attribute)) {
                        continue;
                    }
                    $attributeKey = (string) ($attribute['code'] ?? $attribute['label'] ?? $attributeIndex);
                    $sectionMap[$sectionKey]['attributes'][$attributeKey] ??= [
                        'label' => (string) ($attribute['label'] ?? $attribute['code'] ?? 'Specification'),
                        'values' => [],
                    ];
                    $sectionMap[$sectionKey]['attributes'][$attributeKey]['values'][$productIndex] = $attribute['display_value'] ?? '—';
                }
            }
        }

        $productCount = $projections->count();
        $sections = collect($sectionMap)->map(function (array $section) use ($productCount): array {
            $attributes = collect($section['attributes'])->map(function (array $attribute) use ($productCount): array {
                $values = [];
                for ($index = 0; $index < $productCount; $index++) {
                    $values[] = $attribute['values'][$index] ?? '—';
                }

                return ['label' => $attribute['label'], 'values' => $values];
            })->values()->all();

            return ['label' => $section['label'], 'attributes' => $attributes];
        })->filter(fn (array $section): bool => $section['attributes'] !== [])->values()->all();

        return ['products' => $products, 'sections' => $sections, 'error' => null];
    }
}
