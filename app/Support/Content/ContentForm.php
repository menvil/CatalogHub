<?php

namespace App\Support\Content;

use App\Models\ContentItem;
use App\Rules\Content\UniqueContentSlug;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

final class ContentForm
{
    /** @return array<int, TextInput|Textarea> */
    public static function translationFields(): array
    {
        return [
            TextInput::make('translation_locale')
                ->label('Locale')
                ->required()
                ->maxLength(16),
            TextInput::make('translation_title')
                ->label('Title')
                ->required()
                ->maxLength(255),
            TextInput::make('translation_slug')
                ->label('Slug')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->rule(fn (Get $get, ?ContentItem $record): UniqueContentSlug => new UniqueContentSlug(
                    siteId: (int) $get('site_id'),
                    locale: (string) $get('translation_locale'),
                    ignoreContentItemId: $record?->getKey(),
                )),
            Textarea::make('translation_excerpt')
                ->label('Excerpt')
                ->maxLength(2000),
            Textarea::make('translation_body')
                ->label('Body')
                ->required()
                ->rows(14)
                ->columnSpanFull(),
        ];
    }
}
