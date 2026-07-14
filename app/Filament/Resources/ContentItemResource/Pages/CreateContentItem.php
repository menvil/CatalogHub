<?php

namespace App\Filament\Resources\ContentItemResource\Pages;

use App\Filament\Resources\ContentItemResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

final class CreateContentItem extends CreateRecord
{
    protected static string $resource = ContentItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user instanceof User) {
            $data['site_id'] = $user->isSuperAdmin() ? $data['site_id'] : $user->site_id;
            $data['created_by_user_id'] = $user->getKey();
            $data['updated_by_user_id'] = $user->getKey();
        }

        return $data;
    }
}
