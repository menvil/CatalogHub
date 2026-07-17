<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeDisplayRuleResource\Pages;
use App\Models\AttributeDisplayRule;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

final class AttributeDisplayRuleResource extends Resource
{
    protected static ?string $model = AttributeDisplayRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Attribute Display Rules';

    protected static string|UnitEnum|null $navigationGroup = 'Units';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('attribute_definition_id')
                    ->relationship('attributeDefinition', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('market_code')
                    ->default(AttributeDisplayRule::GLOBAL_MARKET_CODE)
                    ->required()
                    ->maxLength(16),
                TextInput::make('locale')
                    ->default(AttributeDisplayRule::GLOBAL_LOCALE)
                    ->required()
                    ->maxLength(16)
                    ->unique(
                        table: 'attribute_display_rules',
                        column: 'locale',
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                            ->where('attribute_definition_id', $get('attribute_definition_id'))
                            ->where('market_code', $get('market_code') ?: AttributeDisplayRule::GLOBAL_MARKET_CODE),
                    ),
                Select::make('display_unit_id')
                    ->relationship('displayUnit', 'name')
                    ->searchable()
                    ->preload(),
                TextInput::make('decimals')
                    ->integer()
                    ->minValue(0)
                    ->maxValue(255),
                Select::make('rounding_mode')
                    ->required()
                    ->options([
                        'half_up' => 'Half up',
                        'floor' => 'Floor',
                        'ceil' => 'Ceil',
                    ])
                    ->default('half_up'),
                Select::make('suffix_style')
                    ->required()
                    ->options([
                        'symbol' => 'Symbol',
                        'code' => 'Code',
                    ])
                    ->default('symbol'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attributeDefinition.code')
                    ->label('Attribute')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('market_code')
                    ->sortable(),
                TextColumn::make('locale')
                    ->sortable(),
                TextColumn::make('displayUnit.code')
                    ->label('Display Unit')
                    ->sortable(),
                TextColumn::make('decimals')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->can('central.manage');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributeDisplayRules::route('/'),
            'create' => Pages\CreateAttributeDisplayRule::route('/create'),
            'edit' => Pages\EditAttributeDisplayRule::route('/{record}/edit'),
        ];
    }
}
