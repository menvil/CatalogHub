<?php

namespace App\Filament\Resources\ContentItemResource\Pages;

use App\Enums\ContentType;
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
        $isFaq = $data['type'] === ContentType::Faq->value;
        $translation = [
            'locale' => $data['translation_locale'],
            'title' => $data['translation_title'],
            'slug' => $data['translation_slug'],
            'excerpt' => $data['translation_excerpt'] ?? null,
            'body' => $isFaq ? null : $data['translation_body'],
            'body_json' => $isFaq ? $this->faqItems($data['translation_body_json']) : null,
            'meta_title' => $data['translation_meta_title'] ?? null,
            'meta_description' => $data['translation_meta_description'] ?? null,
            'og_title' => $data['translation_og_title'] ?? null,
            'og_description' => $data['translation_og_description'] ?? null,
            'status' => $data['status'] === 'published' ? 'published' : 'draft',
        ];

        foreach (['locale', 'title', 'slug', 'excerpt', 'body', 'body_json', 'meta_title', 'meta_description', 'og_title', 'og_description'] as $key) {
            unset($data['translation_'.$key]);
        }

        return $translation;
    }

    /**
     * @param  array<int, array{question: string, answer: string}>  $items
     * @return array<int, array{question: string, answer: string, position: int}>
     */
    private function faqItems(array $items): array
    {
        $items = array_values($items);

        return array_map(
            fn (array $item, int $position): array => [...$item, 'position' => $position],
            $items,
            array_keys($items),
        );
    }

    private function contentItem(): ContentItem
    {
        if (! $this->record instanceof ContentItem) {
            throw new LogicException('The created content record is unavailable.');
        }

        return $this->record;
    }
}
