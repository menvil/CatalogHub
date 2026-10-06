<?php

namespace App\Actions\CategorySchema;

use App\Enums\Permission;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\User;
use App\Services\Categories\CategoryAccess;
use Illuminate\Validation\ValidationException;

final class DeleteAttributeOptionAction
{
    public function handle(AttributeOption $option, ?User $actor = null): void
    {
        app(CategoryAccess::class)->authorize(Permission::CatalogSchemaManage, $actor);
        throw ValidationException::withMessages(['option' => 'Hard option removal is deferred. Hide with is_visible=false to preserve historical vocabulary.']);
    }
}
