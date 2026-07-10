<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Filament\Resources\CentralProductResource;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\CentralCatalog\CentralProduct;
use App\Services\ProductAttributes\CanonicalValuePreviewer;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

final class ProductSpecsEditor extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CentralProductResource::class;

    protected string $view = 'filament.resources.central-product-resource.pages.product-specs-editor';

    protected static ?string $title = 'Product Specs';

    private ?CentralProduct $cachedProduct = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $values = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->hydrateValues();
    }

    public function getTitle(): string
    {
        return 'Product Specs';
    }

    public function getProduct(): CentralProduct
    {
        if ($this->cachedProduct !== null) {
            return $this->cachedProduct;
        }

        /** @var CentralProduct $product */
        $product = $this->getRecord();

        return $this->cachedProduct = $product->loadMissing([
            'category.attributeSections' => fn ($query) => $query->ordered(),
            'category.attributeSections.attributes' => fn ($query) => $query->ordered(),
            'category.attributeSections.attributes.options' => fn ($query) => $query->ordered(),
            'attributeValues.attributeDefinition',
        ]);
    }

    private function hydrateValues(): void
    {
        $product = $this->getProduct();

        if (! $product->category) {
            $this->values = [];

            return;
        }

        foreach ($product->category->attributeSections as $section) {
            foreach ($section->attributes as $attribute) {
                $existingValue = $product->attributeValues->firstWhere('attribute_definition_id', $attribute->id);

                $this->values[$attribute->id] = $this->stateForAttribute($attribute, $existingValue);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function stateForAttribute(AttributeDefinition $attribute, ?CentralProductAttributeValue $existingValue): array
    {
        return [
            'attribute_id' => $attribute->id,
            'value_type' => $attribute->data_type->value,
            'raw_value' => $existingValue?->raw_value,
            'value_text' => $existingValue?->value_text,
            'value_number' => $existingValue?->value_number,
            'value_bool' => $existingValue?->value_bool,
            'value_enum_code' => $existingValue?->value_enum_code,
            'value_json' => $existingValue?->value_json ?? [],
            'value_min' => $existingValue?->value_min,
            'value_max' => $existingValue?->value_max,
            'source_unit' => $existingValue?->source_unit,
            'canonical_value' => $existingValue?->canonical_value,
            'canonical_unit' => $existingValue?->canonical_unit,
            'confidence' => $existingValue?->confidence,
            'source_type' => $existingValue?->source_type,
            'source_id' => $existingValue?->source_id,
            'source_reference' => $existingValue?->source_reference ?? [],
        ];
    }

    /**
     * @return array{value: float|string, unit: string|null, label: string, warning: string|null}|null
     */
    public function canonicalPreviewFor(AttributeDefinition $attribute): ?array
    {
        return app(CanonicalValuePreviewer::class)->preview($attribute, $this->values[$attribute->id] ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('saveSpecs')
                ->label('Save specs')
                ->icon(Heroicon::OutlinedCheck)
                ->disabled(),
        ];
    }
}
