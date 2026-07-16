<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\User;
use InvalidArgumentException;
use Throwable;

final class SnapshotGenerationService
{
    private const SECTION_KEYS = [
        'products',
        'categories',
        'brands',
        'attributes',
        'attribute_values',
        'translations',
        'media_manifest',
        'site_config',
    ];

    public function __construct(
        private readonly ProductsJsonlExporter $products,
        private readonly CategoriesJsonlExporter $categories,
        private readonly BrandsJsonlExporter $brands,
        private readonly AttributesJsonlExporter $attributes,
        private readonly AttributeValuesJsonlExporter $attributeValues,
        private readonly TranslationsJsonlExporter $translations,
        private readonly MediaManifestJsonlExporter $mediaManifest,
        private readonly SiteConfigJsonlExporter $siteConfig,
    ) {}

    /** @return list<string> */
    public static function sectionKeys(): array
    {
        return self::SECTION_KEYS;
    }

    /** @param list<string> $sections */
    public function generate(
        User $admin,
        array $sections,
        string $snapshotType = 'full',
    ): CatalogSnapshot {
        $sections = array_values(array_unique($sections));

        if ($sections === [] || array_diff($sections, self::SECTION_KEYS) !== []) {
            throw new InvalidArgumentException('Snapshot sections must contain supported export keys.');
        }

        $snapshot = CatalogSnapshot::query()->create([
            'status' => 'pending',
            'snapshot_type' => $snapshotType,
            'storage_disk' => 'local',
            'metadata_json' => ['included_sections' => $sections],
            'created_by_user_id' => $admin->getKey(),
        ]);
        $snapshot->markGenerating();

        try {
            foreach ($sections as $section) {
                $this->exporter($section)->export($snapshot);
            }

            return $snapshot->fresh()->markCompleted();
        } catch (Throwable $exception) {
            $snapshot->markFailed($exception->getMessage());

            throw $exception;
        }
    }

    private function exporter(string $section): JsonlExporter
    {
        return match ($section) {
            'products' => $this->products,
            'categories' => $this->categories,
            'brands' => $this->brands,
            'attributes' => $this->attributes,
            'attribute_values' => $this->attributeValues,
            'translations' => $this->translations,
            'media_manifest' => $this->mediaManifest,
            'site_config' => $this->siteConfig,
        };
    }
}
