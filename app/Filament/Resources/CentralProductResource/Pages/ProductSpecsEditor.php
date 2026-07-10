<?php

namespace App\Filament\Resources\CentralProductResource\Pages;

use App\Filament\Resources\CentralProductResource;
use App\Models\CentralCatalog\CentralProduct;
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

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
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
