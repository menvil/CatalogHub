<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Filament\Resources\CentralCategoryResource;
use App\Models\CentralCatalog\CentralCategory;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

final class CategorySchemaBuilder extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CentralCategoryResource::class;

    protected string $view = 'filament.resources.central-category-resource.pages.category-schema-builder';

    protected static ?string $title = 'Category Schema Builder';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'Category Schema Builder';
    }

    public function getCategory(): CentralCategory
    {
        /** @var CentralCategory $category */
        $category = $this->getRecord();

        return $category->load([
            'attributeSections' => fn ($query) => $query->ordered(),
            'attributeSections.attributes' => fn ($query) => $query->ordered(),
        ]);
    }
}
