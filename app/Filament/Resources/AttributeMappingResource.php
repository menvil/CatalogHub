<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeMappingResource\Pages;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\Imports\AttributeMapping;
use App\Models\User;
use App\Services\Imports\AttributeMappingService;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

final class AttributeMappingResource extends Resource
{
    protected static ?string $model = AttributeMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Imports';

    protected static ?string $navigationLabel = 'Mapping rules';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('import_source_id')
                ->label('Import source')
                ->relationship('source', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'name')
                ->searchable()
                ->preload()
                ->live()
                ->required(),
            TextInput::make('raw_key')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (?string $state, Set $set): mixed => $set(
                    'normalized_raw_key',
                    app(AttributeMappingService::class)->normalizeRawKey((string) $state),
                ))
                ->unique(
                    table: 'attribute_mappings',
                    column: 'raw_key',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                        ->where('import_source_id', $get('import_source_id'))
                        ->where('category_id', $get('category_id')),
                ),
            TextInput::make('normalized_raw_key')->required()->maxLength(255),
            Select::make('attribute_definition_id')
                ->label('Canonical attribute')
                ->options(fn (Get $get): array => AttributeDefinition::query()
                    ->where('central_category_id', $get('category_id'))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->required(fn (Get $get): bool => $get('status') === 'reviewed')
                ->rule(fn (Get $get) => Rule::exists('attribute_definitions', 'id')
                    ->where('central_category_id', $get('category_id'))),
            TextInput::make('confidence')
                ->numeric()
                ->minValue(0)
                ->maxValue(1)
                ->step(0.0001)
                ->default(0),
            Select::make('status')
                ->options([
                    'auto' => 'Auto / unreviewed',
                    'reviewed' => 'Reviewed',
                    'rejected' => 'Rejected',
                ])
                ->default('auto')
                ->live()
                ->required(),
            Select::make('mapping_type')
                ->options(['attribute' => 'Attribute'])
                ->default('attribute')
                ->required(),
            Textarea::make('notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('source.name')->label('Source')->searchable()->sortable(),
                TextColumn::make('category.name')->label('Category')->searchable()->sortable(),
                TextColumn::make('raw_key')->searchable(),
                TextColumn::make('normalized_raw_key')->searchable(),
                TextColumn::make('attributeDefinition.name')->label('Canonical attribute')->placeholder('Unmapped'),
                TextColumn::make('confidence')->numeric(decimalPlaces: 4),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('usage_count')
                    ->label('Usage')
                    ->state(fn (AttributeMapping $record): int => app(AttributeMappingService::class)->usageCount($record)),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->relationship('source', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        'auto' => 'Auto / unreviewed',
                        'reviewed' => 'Reviewed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributeMappings::route('/'),
            'create' => Pages\CreateAttributeMapping::route('/create'),
            'edit' => Pages\EditAttributeMapping::route('/{record}/edit'),
        ];
    }
}
