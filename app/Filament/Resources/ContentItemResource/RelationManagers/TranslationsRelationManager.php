<?php

namespace App\Filament\Resources\ContentItemResource\RelationManagers;

use App\Enums\ContentType;
use App\Models\ContentItem;
use App\Models\ContentTranslation;
use App\Rules\Content\UniqueContentSlug;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use LogicException;

final class TranslationsRelationManager extends RelationManager
{
    protected static string $relationship = 'translations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('locale')
                ->options(fn (): array => $this->enabledLocaleOptions())
                ->rules([fn () => Rule::in(array_keys($this->enabledLocaleOptions()))])
                ->unique(
                    table: 'content_translations',
                    column: 'locale',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule): Unique => $rule
                        ->where('content_item_id', $this->contentItem()->getKey()),
                )
                ->required(),
            TextInput::make('title')->required()->maxLength(255),
            TextInput::make('slug')
                ->required()
                ->alphaDash()
                ->maxLength(255)
                ->rule(fn (Get $get, ?ContentTranslation $record): UniqueContentSlug => new UniqueContentSlug(
                    siteId: (int) $this->contentItem()->site_id,
                    locale: (string) $get('locale'),
                    ignoreContentItemId: $record?->content_item_id,
                )),
            Select::make('status')
                ->options(['draft' => 'Draft', 'published' => 'Published'])
                ->default('draft')
                ->required(),
            Textarea::make('excerpt')->maxLength(2000),
            Textarea::make('body')
                ->required(fn (): bool => ! $this->isFaq())
                ->visible(fn (): bool => ! $this->isFaq())
                ->rows(14)
                ->columnSpanFull(),
            Repeater::make('body_json')
                ->label('Questions and answers')
                ->schema([
                    TextInput::make('question')->required()->maxLength(1000),
                    Textarea::make('answer')->required()->rows(5),
                ])
                ->minItems(1)
                ->required()
                ->visible(fn (): bool => $this->isFaq())
                ->columnSpanFull(),
            TextInput::make('meta_title')->maxLength(255),
            Textarea::make('meta_description')->maxLength(2000),
            TextInput::make('og_title')->maxLength(255),
            Textarea::make('og_description')->maxLength(2000),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('locale')->badge()->sortable(),
                TextColumn::make('title')->searchable(),
                TextColumn::make('slug'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make()]);
    }

    /** @return array<string, string> */
    private function enabledLocaleOptions(): array
    {
        $item = $this->contentItem();
        $locales = DB::table('site_locales')
            ->where('site_id', $item->site_id)
            ->where('is_enabled', true)
            ->orderBy('position')
            ->pluck('locale_code', 'locale_code')
            ->all();

        if ($locales === []) {
            return [$item->site->default_locale => $item->site->default_locale];
        }

        return $locales;
    }

    private function isFaq(): bool
    {
        return $this->contentItem()->type === ContentType::Faq;
    }

    private function contentItem(): ContentItem
    {
        $record = $this->getOwnerRecord();

        if (! $record instanceof ContentItem) {
            throw new LogicException('The translation owner must be a content item.');
        }

        return $record;
    }
}
