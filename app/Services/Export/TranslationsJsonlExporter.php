<?php

namespace App\Services\Export;

use App\Models\CatalogSnapshot;
use App\Models\ContentTranslation;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;
use BackedEnum;
use Generator;
use Illuminate\Database\Eloquent\Model;

final class TranslationsJsonlExporter
{
    private const SOURCES = [
        [
            'model' => ProductTranslation::class,
            'entity_type' => 'product',
            'entity_key' => 'product_id',
            'fields' => ['name', 'subtitle', 'short_description', 'description', 'seo_title', 'seo_description'],
        ],
        [
            'model' => CategoryTranslation::class,
            'entity_type' => 'category',
            'entity_key' => 'category_id',
            'fields' => ['name', 'description', 'seo_title', 'seo_description'],
        ],
        [
            'model' => AttributeSectionTranslation::class,
            'entity_type' => 'attribute_section',
            'entity_key' => 'attribute_section_id',
            'fields' => ['name', 'description'],
        ],
        [
            'model' => AttributeTranslation::class,
            'entity_type' => 'attribute_definition',
            'entity_key' => 'attribute_definition_id',
            'fields' => ['label', 'short_label', 'help_text'],
        ],
        [
            'model' => AttributeOptionTranslation::class,
            'entity_type' => 'attribute_option',
            'entity_key' => 'attribute_option_id',
            'fields' => ['label', 'description'],
        ],
        [
            'model' => UnitTranslation::class,
            'entity_type' => 'measurement_unit',
            'entity_key' => 'measurement_unit_id',
            'fields' => ['short_name', 'long_name', 'plural_name', 'symbol_position', 'space_between_value_and_unit'],
        ],
        [
            'model' => ContentTranslation::class,
            'entity_type' => 'content_item',
            'entity_key' => 'content_item_id',
            'fields' => ['slug', 'title', 'excerpt', 'body', 'body_json', 'meta_title', 'meta_description', 'og_title', 'og_description'],
        ],
    ];

    public function __construct(private readonly JsonlStreamWriter $writer) {}

    public function export(CatalogSnapshot $snapshot): JsonlExportResult
    {
        return $this->writer->write($snapshot, 'translations', $this->rows());
    }

    /** @return Generator<int, array<string, mixed>> */
    private function rows(): Generator
    {
        foreach (self::SOURCES as $source) {
            foreach ($source['model']::query()->orderBy('id')->cursor() as $translation) {
                yield $this->row($translation, $source);
            }
        }
    }

    /**
     * @param  array{entity_type: string, entity_key: string, fields: list<string>}  $source
     * @return array<string, mixed>
     */
    private function row(Model $translation, array $source): array
    {
        $status = $translation->getAttribute('status');

        return [
            'entity_type' => $source['entity_type'],
            'entity_id' => $translation->getAttribute($source['entity_key']),
            'locale' => $translation->getAttribute('locale'),
            'field' => 'fields',
            'value' => collect($source['fields'])
                ->mapWithKeys(fn (string $field): array => [$field => $translation->getAttribute($field)])
                ->all(),
            'status' => $status instanceof BackedEnum ? $status->value : $status,
            'source_hash' => $translation->getAttribute('source_hash'),
            'approved_at' => $translation->getAttribute('approved_at')?->toISOString(),
            'updated_at' => $translation->getAttribute('updated_at')?->toISOString(),
        ];
    }
}
