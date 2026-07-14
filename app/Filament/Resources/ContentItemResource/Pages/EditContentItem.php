<?php

namespace App\Filament\Resources\ContentItemResource\Pages;

use App\Filament\Resources\ContentItemResource;
use App\Models\ContentItem;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LogicException;

final class EditContentItem extends EditRecord
{
    protected static string $resource = ContentItemResource::class;

    /** @var array<string, mixed> */
    private array $translationData = [];

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationData = $this->extractTranslationData($data);
        $user = auth()->user();

        if ($user instanceof User) {
            $data['site_id'] = $user->isSuperAdmin() ? $data['site_id'] : $user->site_id;
            $data['updated_by_user_id'] = $user->getKey();
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $item = $this->contentItem();
        $translation = $item->translations()
            ->where('locale', $item->site->default_locale)
            ->first();

        if ($translation === null) {
            $translation = $item->translations()->first();
        }

        $data['translation_locale'] = $translation === null
            ? $item->site->default_locale
            : $translation->locale;
        $data['translation_title'] = $translation?->title;
        $data['translation_slug'] = $translation?->slug;
        $data['translation_excerpt'] = $translation?->excerpt;
        $data['translation_body'] = $translation?->body;

        return $data;
    }

    protected function afterSave(): void
    {
        $this->contentItem()->translations()->updateOrCreate(
            ['locale' => $this->translationData['locale']],
            $this->translationData,
        );
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

        foreach (['locale', 'title', 'slug', 'excerpt', 'body'] as $key) {
            unset($data['translation_'.$key]);
        }

        return $translation;
    }

    private function contentItem(): ContentItem
    {
        if (! $this->record instanceof ContentItem) {
            throw new LogicException('The content record is unavailable.');
        }

        return $this->record;
    }
}
