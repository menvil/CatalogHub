<?php

namespace App\Filament\Resources\FacetDefinitionResource\Pages;

use App\Actions\Facets\SaveCategoryFacetAction;
use App\Filament\Resources\FacetDefinitionResource;
use App\Models\CentralCatalog\CentralCategory;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

final class CreateFacetDefinition extends CreateRecord
{
    protected static string $resource = FacetDefinitionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $category = CentralCategory::query()->findOrFail($data['category_id']);
        $expectedRevision = (int) $data['expected_schema_revision'];
        unset($data['category_id'], $data['expected_schema_revision']);

        return app(SaveCategoryFacetAction::class)->handle($category, null, $data, $expectedRevision, auth()->user());
    }
}
