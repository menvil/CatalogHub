<?php

namespace App\Filament\Resources\FacetDefinitionResource\Pages;

use App\Actions\Facets\SaveCategoryFacetAction;
use App\Filament\Resources\FacetDefinitionResource;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

final class EditFacetDefinition extends EditRecord
{
    protected static string $resource = FacetDefinitionResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['expected_schema_revision'] = CentralCategory::query()->whereKey($data['category_id'])->value('schema_revision');

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof FacetDefinition) {
            throw new \LogicException('Unexpected record owner.');
        }
        $category = CentralCategory::query()->findOrFail($data['category_id']);
        $expectedRevision = (int) $data['expected_schema_revision'];
        unset($data['category_id'], $data['expected_schema_revision']);

        return app(SaveCategoryFacetAction::class)->handle($category, $record, $data, $expectedRevision, auth()->user());
    }
}
