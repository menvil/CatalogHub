<?php

namespace App\Filament\Resources\ContentItemResource\Pages;

use App\Filament\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use LogicException;

final class CreateContentItem extends CreateRecord
{
    protected static string $resource = ContentItemResource::class;

    /** @var array<string, mixed> */
    private array $translationData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationData = $this->extractTranslationData($data);
        $user = auth()->user();

        if ($user instanceof User) {
            $data['site_id'] = $user->isSuperAdmin() ? $data['site_id'] : $user->site_id;
            $data['created_by_user_id'] = $user->getKey();
            $data['updated_by_user_id'] = $user->getKey();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->contentItem()->translations()->create($this->translationData);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function extractTranslationData(array &$data): array
    {
        $translation = [
            'locale' => $data['translation_locale'],
            'title' => $data['translation_title'],
            'slug' => $data['translation_slug'],
            'excerpt' => $data['translation_excerpt'] ?? null,
            'body' => $data['translation_body'],
            'status' => $data['status'] === 'published' ? 'published' : 'draft',
        ];

        foreach (array_keys($translation) as $key) {
            unset($data['translation_'.$key]);
        }

        return $translation;
    }

    private function contentItem(): ContentItem
    {
        if (! $this->record instanceof ContentItem) {
            throw new LogicException('The created content record is unavailable.');
        }

        return $this->record;
    }
}
