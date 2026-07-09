<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Actions\CategorySchema\CreateAttributeDefinitionAction;
use App\Actions\CategorySchema\CreateAttributeOptionAction;
use App\Actions\CategorySchema\CreateAttributeSectionAction;
use App\Actions\CategorySchema\DeleteAttributeOptionAction;
use App\Actions\CategorySchema\DeleteAttributeSectionAction;
use App\Actions\CategorySchema\MoveAttributeDefinitionAction;
use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Actions\CategorySchema\UpdateAttributeOptionAction;
use App\Actions\CategorySchema\UpdateAttributeSectionAction;
use App\Filament\Resources\CentralCategoryResource;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\CategorySchema\CategorySchemaPreviewBuilder;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

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

    /**
     * @return list<array<string, mixed>>
     */
    public function getSchemaPreview(): array
    {
        return app(CategorySchemaPreviewBuilder::class)->build($this->getCategory());
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateSection(int $sectionId, array $data, UpdateAttributeSectionAction $action): void
    {
        $section = $this->getCategory()->attributeSections()->findOrFail($sectionId);

        $action->handle($section, $data);
    }

    public function deleteSection(int $sectionId, DeleteAttributeSectionAction $action): void
    {
        $section = $this->getCategory()->attributeSections()->findOrFail($sectionId);

        $action->handle($section);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAttribute(int $sectionId, array $data, CreateAttributeDefinitionAction $action): void
    {
        $section = $this->getCategory()->attributeSections()->findOrFail($sectionId);

        $action->handle($section, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAttribute(int $attributeId, array $data, UpdateAttributeDefinitionAction $action): void
    {
        $attribute = $this->getCategory()->attributeDefinitions()->findOrFail($attributeId);

        $action->handle($attribute, $data);
    }

    public function moveAttribute(int $attributeId, int $targetSectionId, int $position, MoveAttributeDefinitionAction $action): void
    {
        $category = $this->getCategory();
        $attribute = $category->attributeDefinitions()->findOrFail($attributeId);
        $targetSection = $category->attributeSections()->findOrFail($targetSectionId);

        $action->handle($attribute, $targetSection, $position);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createOption(int $attributeId, array $data, CreateAttributeOptionAction $action): void
    {
        $attribute = $this->getCategory()->attributeDefinitions()->findOrFail($attributeId);

        $action->handle($attribute, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateOption(int $attributeId, int $optionId, array $data, UpdateAttributeOptionAction $action): void
    {
        $attribute = $this->getCategory()->attributeDefinitions()->findOrFail($attributeId);
        $option = $attribute->options()->findOrFail($optionId);

        $action->handle($option, $data);
    }

    public function deleteOption(int $attributeId, int $optionId, DeleteAttributeOptionAction $action): void
    {
        $attribute = $this->getCategory()->attributeDefinitions()->findOrFail($attributeId);
        $option = $attribute->options()->findOrFail($optionId);

        $action->handle($option);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createSection')
                ->label('Add section')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->required()
                        ->regex('/^[a-z][a-z0-9_]*$/')
                        ->maxLength(255),
                    Select::make('display_style')
                        ->required()
                        ->options([
                            'table' => 'Table',
                            'list' => 'List',
                        ])
                        ->default('table'),
                    TextInput::make('position')
                        ->integer()
                        ->minValue(0),
                    Toggle::make('is_collapsible')
                        ->default(true),
                    Toggle::make('is_visible')
                        ->default(true),
                ])
                ->action(function (array $data, CreateAttributeSectionAction $action): void {
                    $action->handle($this->getCategory(), $data);
                }),
        ];
    }
}
