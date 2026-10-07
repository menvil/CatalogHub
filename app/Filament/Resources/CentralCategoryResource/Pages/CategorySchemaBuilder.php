<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Actions\CategorySchema\ApproveCategorySchemaAction;
use App\Actions\CategorySchema\ArchiveCategorySchemaAction;
use App\Actions\CategorySchema\CloneCategorySchemaAction;
use App\Actions\CategorySchema\CreateAttributeDefinitionAction;
use App\Actions\CategorySchema\CreateAttributeOptionAction;
use App\Actions\CategorySchema\CreateAttributeSectionAction;
use App\Actions\CategorySchema\DeleteAttributeOptionAction;
use App\Actions\CategorySchema\DeleteAttributeSectionAction;
use App\Actions\CategorySchema\ExportCategorySchemaAction;
use App\Actions\CategorySchema\MarkCategorySchemaReviewedAction;
use App\Actions\CategorySchema\MoveAttributeDefinitionAction;
use App\Actions\CategorySchema\RestoreCategorySchemaAction;
use App\Actions\CategorySchema\UpdateAssignedAttributeAction;
use App\Actions\CategorySchema\UpdateAttributeDefinitionAction;
use App\Actions\CategorySchema\UpdateAttributeOptionAction;
use App\Actions\CategorySchema\UpdateAttributeSectionAction;
use App\DTO\CategorySchema\CategorySchemaIssue;
use App\Enums\CategorySchemaStatus;
use App\Enums\Permission;
use App\Exceptions\CategorySchema\CannotTransitionCategorySchemaStatusException;
use App\Filament\Resources\CentralCategoryResource;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\Categories\CategoryAccess;
use App\Services\CategorySchema\CategorySchemaPreviewBuilder;
use App\Services\CategorySchema\CategorySchemaValidator;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

final class CategorySchemaBuilder extends Page
{
    use InteractsWithRecord;

    protected static string $resource = CentralCategoryResource::class;

    protected string $view = 'filament.resources.central-category-resource.pages.category-schema-builder';

    protected static ?string $title = 'Category Schema Builder';

    private ?CentralCategory $cachedCategory = null;

    #[Locked]
    public int $schemaRevision = 1;

    public static function canAccess(array $parameters = []): bool
    {
        return app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage);
    }

    public static function authorizeResourceAccess(): void
    {
        abort_unless(self::canAccess(), 403);
    }

    public function getResourceBreadcrumbs(): array
    {
        return CentralCategoryResource::canViewAny() ? parent::getResourceBreadcrumbs() : [];
    }

    public function mount(int|string $record): void
    {
        abort_unless(self::canAccess(), 403);
        $this->record = $this->resolveRecord($record);
        $this->schemaRevision = $this->getCategory()->schema_revision;
    }

    /** @return list<array{section: AttributeSection|null, assignments: Collection}> */
    public function getAssignmentGroups(): array
    {
        $category = $this->getCategory();
        $groups = $category->attributeSections->map(fn ($section) => ['section' => $section, 'assignments' => $category->attributeAssignments->where('attribute_section_id', $section->id)->sortBy(fn ($row) => [$row->position, $row->id])])->all();
        $ungrouped = $category->attributeAssignments->whereNull('attribute_section_id')->sortBy(fn ($row) => [$row->position, $row->id]);
        if ($ungrouped->isNotEmpty()) {
            $groups[] = ['section' => null, 'assignments' => $ungrouped];
        }

        return $groups;
    }

    public function getTitle(): string
    {
        return 'Category Schema Builder';
    }

    public function getCategory(): CentralCategory
    {
        if ($this->cachedCategory !== null) {
            return $this->cachedCategory;
        }

        /** @var CentralCategory $category */
        $category = $this->getRecord();

        return $this->cachedCategory = $category->loadMissing([
            'attributeSections' => fn ($query) => $query->ordered(),
            'attributeSections.assignments' => fn ($query) => $query->ordered()->with('definition'),
            'attributeAssignments.definition',
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
     * @return list<CategorySchemaIssue>
     */
    public function getSchemaIssues(): array
    {
        return app(CategorySchemaValidator::class)->validate($this->getCategory())->issues();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSection(int $sectionId, array $data, UpdateAttributeSectionAction $action): void
    {
        $section = $this->getCategory()->attributeSections()->findOrFail($sectionId);

        $action->handle($section, $data);
        $this->reloadSchema();
    }

    public function deleteSection(int $sectionId, DeleteAttributeSectionAction $action): void
    {
        $section = $this->getCategory()->attributeSections()->findOrFail($sectionId);

        $action->handle($section);
        $this->reloadSchema();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAttribute(int $sectionId, array $data, CreateAttributeDefinitionAction $action): void
    {
        $section = $this->getCategory()->attributeSections()->findOrFail($sectionId);

        $action->handle($section, $data);
        $this->reloadSchema();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAttribute(int $attributeId, array $data, UpdateAttributeDefinitionAction $action): void
    {
        $assignment = $this->getCategory()->attributeAssignments()->where('attribute_definition_id', $attributeId)->firstOrFail();
        app(UpdateAssignedAttributeAction::class)->handle($assignment, $data, $this->schemaRevision);
        $this->reloadSchema();
    }

    public function moveAttribute(int $attributeId, int $targetSectionId, int $position, MoveAttributeDefinitionAction $action): void
    {
        $category = $this->getCategory();
        $attribute = $category->attributeAssignments()->where('attribute_definition_id', $attributeId)->firstOrFail();
        $targetSection = $category->attributeSections()->findOrFail($targetSectionId);

        $action->handle($attribute, $targetSection, $position);
        $this->reloadSchema();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createOption(int $attributeId, array $data, CreateAttributeOptionAction $action): void
    {
        $attribute = $this->getCategory()->attributeAssignments()->where('attribute_definition_id', $attributeId)->firstOrFail()->definition;
        $action->handle($attribute, $data);
        $this->reloadSchema();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOption(int $attributeId, int $optionId, array $data, UpdateAttributeOptionAction $action): void
    {
        $attribute = $this->getCategory()->attributeAssignments()->where('attribute_definition_id', $attributeId)->firstOrFail()->definition;
        $option = $attribute->options()->findOrFail($optionId);

        $action->handle($option, $data);
        $this->reloadSchema();
    }

    public function deleteOption(int $attributeId, int $optionId, DeleteAttributeOptionAction $action): void
    {
        $attribute = $this->getCategory()->attributeAssignments()->where('attribute_definition_id', $attributeId)->firstOrFail()->definition;
        $option = $attribute->options()->findOrFail($optionId);

        $action->handle($option);
        $this->reloadSchema();
    }

    public function markReviewed(MarkCategorySchemaReviewedAction $action): void
    {
        $action->handle($this->getCategory(), $this->schemaRevision);
        $this->reloadSchema();
    }

    public function approveSchema(ApproveCategorySchemaAction $action): void
    {
        $action->handle($this->getCategory(), $this->schemaRevision);
        $this->reloadSchema();
    }

    public function archiveSchema(ArchiveCategorySchemaAction $action): void
    {
        $action->handle($this->getCategory(), $this->schemaRevision);
        $this->reloadSchema();
    }

    public function cloneSchemaFrom(int $sourceCategoryId, CloneCategorySchemaAction $action): void
    {
        $source = CentralCategory::query()->findOrFail($sourceCategoryId);

        $action->handle($source, $this->getCategory());
        $this->reloadSchema();
    }

    /**
     * @return array<string, mixed>
     */
    public function exportSchema(ExportCategorySchemaAction $action): array
    {
        return $action->handle($this->getCategory());
    }

    public function restoreSchema(RestoreCategorySchemaAction $action): void
    {
        try {
            $action->handle($this->getCategory(), $this->schemaRevision);
        } catch (CannotTransitionCategorySchemaStatusException $exception) {
            throw ValidationException::withMessages(['schema_status' => $exception->getMessage()]);
        }
        $this->reloadSchema();
    }

    private function reloadSchema(): void
    {
        $this->cachedCategory = null;
        $this->record = $this->getRecord()->fresh();
        $this->schemaRevision = $this->getCategory()->schema_revision;
    }

    private function mayMutateSchema(): bool
    {
        return app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage, true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('review')->visible(fn (): bool => $this->mayMutateSchema() && $this->getCategory()->schema_status === CategorySchemaStatus::Draft)
                ->action(fn (MarkCategorySchemaReviewedAction $action) => $this->markReviewed($action)),
            Action::make('approve')->visible(fn (): bool => $this->mayMutateSchema() && $this->getCategory()->schema_status === CategorySchemaStatus::Reviewed)
                ->action(fn (ApproveCategorySchemaAction $action) => $this->approveSchema($action)),
            Action::make('archiveSchema')->requiresConfirmation()->visible(fn (): bool => $this->mayMutateSchema() && $this->getCategory()->schema_status === CategorySchemaStatus::Approved)
                ->action(fn (ArchiveCategorySchemaAction $action) => $this->archiveSchema($action)),
            Action::make('restoreSchema')->visible(fn (): bool => $this->mayMutateSchema() && $this->getCategory()->schema_status === CategorySchemaStatus::Archived)
                ->action(fn (RestoreCategorySchemaAction $action) => $this->restoreSchema($action)),
            Action::make('createSection')
                ->label('Add section')
                ->visible(fn (): bool => app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage, true) && $this->getCategory()->schema_status !== CategorySchemaStatus::Archived)
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('code')
                        ->required()
                        ->regex('/\A[a-z][a-z0-9_]*\z/')
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
                        ->minValue(0)
                        ->maxValue(AttributeSection::MAX_POSITION),
                    Toggle::make('is_collapsible')
                        ->default(true),
                    Toggle::make('is_visible')
                        ->default(true),
                ])
                ->action(function (array $data, CreateAttributeSectionAction $action): void {
                    $action->handle($this->getCategory(), $data);
                    $this->reloadSchema();
                }),
        ];
    }
}
