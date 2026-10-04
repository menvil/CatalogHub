<?php

namespace App\Filament\Resources\CentralCategoryResource\Pages;

use App\Actions\CentralCatalog\CreateCentralCategoryAction;
use App\Enums\Permission;
use App\Filament\Resources\CentralCategoryResource;
use App\Services\Categories\CategoryAccess;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateCentralCategory extends CreateRecord
{
    protected static string $resource = CentralCategoryResource::class;

    protected ?bool $hasDatabaseTransactions = false;

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $actor = app(CategoryAccess::class)->authorize(Permission::CatalogCategoriesManage);

        return app(CreateCentralCategoryAction::class)->handle($actor,
            array_intersect_key($data, array_flip(['name', 'slug', 'parent_id'])),
            (int) $data['new_hierarchy_revision']);
    }
}
