<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Actions\CentralCatalog\ActivateCentralCategoryAction;
use App\Actions\CentralCatalog\ArchiveCentralCategoryAction;
use App\Actions\CentralCatalog\RestoreCentralCategoryAction;
use App\Actions\CentralCatalog\SaveLegacyCentralCategoryAction;
use App\Enums\CentralCategoryStatus;
use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource;
use App\Models\CentralCatalog\CentralCategory;
use App\Services\Categories\CategoryAccess;
use App\Services\Categories\CategoryHierarchy;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;

final class EditCentralCategory extends EditRecord
{
    protected static string $resource = CentralCategoryResource::class;

    protected ?bool $hasDatabaseTransactions = false;

    #[Locked]
    public ?int $originalParentId = null;

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->originalParentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $revision = app(CategoryHierarchy::class)->revision($this->originalParentId);

        return [...$data, 'old_hierarchy_revision' => $revision, 'new_hierarchy_revision' => $revision];
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof CentralCategory) {
            throw new \LogicException('Expected a Category record.');
        }
        $actor = app(CategoryAccess::class)->authorize(Permission::CatalogCategoriesManage);

        return app(SaveLegacyCentralCategoryAction::class)->handle($actor, $record,
            array_intersect_key($data, array_flip(['name', 'slug'])),
            $this->originalParentId, isset($data['parent_id']) ? (int) $data['parent_id'] : null,
            (int) $data['old_hierarchy_revision'], (int) $data['new_hierarchy_revision']);
    }

    protected function afterSave(): void
    {
        $this->getRecord()->refresh();
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('activate')->visible(fn (): bool => $this->mayMutate() && $this->category()->status === CentralCategoryStatus::Draft)
                ->action(fn () => $this->changeLifecycle(ActivateCentralCategoryAction::class)),
            Action::make('archive')->requiresConfirmation()->visible(fn (): bool => $this->mayMutate() && $this->category()->status !== CentralCategoryStatus::Archived)
                ->action(fn () => $this->changeLifecycle(ArchiveCentralCategoryAction::class)),
            Action::make('restore')->visible(fn (): bool => $this->mayMutate() && $this->category()->status === CentralCategoryStatus::Archived)
                ->action(fn () => $this->changeLifecycle(RestoreCentralCategoryAction::class)),
        ];
    }

    private function mayMutate(): bool
    {
        return app(CategoryAccess::class)->allows(Permission::CatalogCategoriesManage, true);
    }

    private function category(): CentralCategory
    {
        $record = $this->getRecord();
        if (! $record instanceof CentralCategory) {
            throw new \LogicException('Expected a Category record.');
        }

        return $record;
    }

    private function changeLifecycle(string $action): void
    {
        $actor = app(CategoryAccess::class)->authorize(Permission::CatalogCategoriesManage);
        app($action)->handle($actor, $this->category());
        $this->refreshFormData(['status']);
    }
}
