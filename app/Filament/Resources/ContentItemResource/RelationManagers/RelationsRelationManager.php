<?php

namespace App\Filament\Resources\ContentItemResource\RelationManagers;

use App\Enums\ContentRelationTargetType;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ContentItem;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use LogicException;

final class RelationsRelationManager extends RelationManager
{
    protected static string $relationship = 'relations';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('related_type')
                ->options([
                    ContentRelationTargetType::Product->value => ContentRelationTargetType::Product->label(),
                    ContentRelationTargetType::Category->value => ContentRelationTargetType::Category->label(),
                ])
                ->default(ContentRelationTargetType::Product->value)
                ->live()
                ->required(),
            Select::make('related_id')
                ->label(fn (Get $get): string => ContentRelationTargetType::tryFrom(
                    (string) $get('related_type'),
                )?->label() ?? 'Target')
                ->options(fn (Get $get): array => $this->targetOptions((string) $get('related_type')))
                ->searchable()
                ->preload()
                ->unique(
                    table: 'content_relations',
                    column: 'related_id',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                        ->where('content_item_id', $this->contentItem()->getKey())
                        ->where('related_type', $get('related_type')),
                )
                ->required(),
            Select::make('relation_type')
                ->options(['related' => 'Related', 'featured' => 'Featured'])
                ->default('related')
                ->required(),
            TextInput::make('position')->integer()->minValue(0)->default(0)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('related_type')->badge(),
                TextColumn::make('related_id')->label('Target ID'),
                TextColumn::make('relation_type')->badge(),
                TextColumn::make('position')->sortable(),
            ])
            ->defaultSort('position')
            ->headerActions([CreateAction::make()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    private function contentItem(): ContentItem
    {
        $record = $this->getOwnerRecord();

        if (! $record instanceof ContentItem) {
            throw new LogicException('The relation owner must be a content item.');
        }

        return $record;
    }

    /** @return array<int|string, string> */
    private function targetOptions(string $type): array
    {
        return match (ContentRelationTargetType::tryFrom($type)) {
            ContentRelationTargetType::Product => CentralProduct::query()->orderBy('name')->pluck('name', 'id')->all(),
            ContentRelationTargetType::Category => CentralCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
            default => [],
        };
    }
}
