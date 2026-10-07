<?php

namespace App\Filament\Resources;

use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Enums\Permission;
use App\Filament\Resources\FacetDefinitionResource\Pages;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Rules\Facets\ValidFacetDefinitionRule;
use App\Services\Categories\CategoryAccess;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

final class FacetDefinitionResource extends Resource
{
    protected static ?string $model = FacetDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static ?string $navigationLabel = 'Facets';

    protected static string|UnitEnum|null $navigationGroup = 'Central Catalog';

    protected static ?string $recordTitleAttribute = 'code';

    public static function canViewAny(): bool
    {
        return self::canManageFacets();
    }

    public static function canCreate(): bool
    {
        return self::canManageFacets(true);
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManageFacets(true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('category_id')
                ->label('Category')
                ->relationship('category', 'name')
                ->live()
                ->afterStateUpdated(fn (Set $set, $state) => $set('expected_schema_revision', CentralCategory::query()->whereKey($state)->value('schema_revision')))
                ->required()
                ->searchable()
                ->preload(),
            Hidden::make('expected_schema_revision')->required()->rules(['integer']),
            Select::make('source_type')
                ->options(FacetSourceType::options())
                ->required(),
            Select::make('category_attribute_assignment_id')
                ->label('Attribute')
                ->options(fn (Get $get): array => CategoryAttributeAssignment::query()->where('central_category_id', $get('category_id'))->with('definition')->ordered()->get()->mapWithKeys(fn ($a) => [$a->id => $a->definition->name])->all())
                ->searchable()
                ->preload(),
            TextInput::make('code')
                ->required()
                ->maxLength(255)
                ->unique(
                    table: 'facet_definitions',
                    column: 'code',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                        ->where('category_id', $get('category_id')),
                ),
            TextInput::make('label_override')
                ->label('Label override')
                ->maxLength(255),
            Select::make('facet_type')
                ->options(FacetType::options())
                ->rules([new ValidFacetDefinitionRule])
                ->live()
                ->required(),
            TextInput::make('position')
                ->required()
                ->integer()
                ->minValue(0)
                ->default(0),
            Toggle::make('is_active')->default(true),
            Toggle::make('is_filterable')->default(true),
            Toggle::make('is_visible')->default(true),
            Toggle::make('is_collapsible')->default(true),
            Toggle::make('default_collapsed')->default(false),
            TextInput::make('config_json.min')
                ->label('Minimum')
                ->numeric()
                ->visible(fn (Get $get): bool => $get('facet_type') === FacetType::Range->value),
            TextInput::make('config_json.max')
                ->label('Maximum')
                ->numeric()
                ->visible(fn (Get $get): bool => $get('facet_type') === FacetType::Range->value),
            TextInput::make('config_json.step')
                ->label('Step')
                ->numeric()
                ->minValue(0)
                ->visible(fn (Get $get): bool => $get('facet_type') === FacetType::Range->value),
            TextInput::make('config_json.unit_code')
                ->label('Unit code')
                ->maxLength(64)
                ->visible(fn (Get $get): bool => $get('facet_type') === FacetType::Range->value),
            Select::make('config_json.display_mode')
                ->label('Display mode')
                ->options([
                    'toggle' => 'Toggle',
                    'checkbox' => 'Checkbox',
                ])
                ->default('toggle')
                ->visible(fn (Get $get): bool => $get('facet_type') === FacetType::Boolean->value),
            TextInput::make('config_json.placeholder')
                ->label('Placeholder')
                ->maxLength(255)
                ->visible(fn (Get $get): bool => $get('facet_type') === FacetType::Select->value),
            Toggle::make('config_json.searchable')
                ->label('Searchable options')
                ->default(false)
                ->visible(fn (Get $get): bool => $get('facet_type') === FacetType::Select->value),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')->label('Category')->searchable()->sortable(),
                TextColumn::make('code')->searchable()->sortable(),
                TextColumn::make('source_type')->badge()->sortable(),
                TextColumn::make('facet_type')->badge()->sortable(),
                IconColumn::make('is_active')->boolean()->sortable(),
                IconColumn::make('is_visible')->boolean()->sortable(),
                TextColumn::make('position')->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->defaultGroup('category.name')
            ->defaultSort('position')
            ->recordActions([
                EditAction::make()->url(fn ($record): string => self::getUrl('edit', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacetDefinitions::route('/'),
            'create' => Pages\CreateFacetDefinition::route('/create'),
            'edit' => Pages\EditFacetDefinition::route('/{record}/edit'),
        ];
    }

    private static function canManageFacets(bool $mutation = false): bool
    {
        return app(CategoryAccess::class)->allows(Permission::CatalogSchemaManage, $mutation);
    }
}
