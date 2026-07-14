<?php

namespace App\Support\Content;

use App\Enums\ContentType;
use App\Models\ContentItem;
use App\Rules\Content\UniqueContentSlug;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;

final class ContentForm
{
    /** @return array<int, TextInput|Textarea|Repeater> */
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
                ->required(fn (Get $get): bool => $get('type') !== ContentType::Faq->value)
                ->visible(fn (Get $get): bool => $get('type') !== ContentType::Faq->value)
                ->rows(14)
                ->columnSpanFull(),
            Repeater::make('translation_body_json')
                ->label('Questions and answers')
                ->schema([
                    TextInput::make('question')->required()->maxLength(1000),
                    Textarea::make('answer')->required()->rows(5),
                ])
                ->minItems(1)
                ->required()
                ->visible(fn (Get $get): bool => $get('type') === ContentType::Faq->value)
                ->columnSpanFull(),
        ];
    }
}
