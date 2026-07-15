<?php

namespace App\Filament\Resources;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceType;
use App\Enums\PriceSourceUpdateFrequency;
use App\Filament\Resources\PriceSourceResource\Pages;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\User;
use App\Services\Pricing\PriceSourceCredentialService;
use App\Services\Pricing\PriceSourceSyncService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use UnitEnum;

final class PriceSourceResource extends Resource
{
    protected static ?string $model = PriceSource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?string $navigationLabel = 'Price sources';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return self::canManagePrices();
    }

    public static function canView(Model $record): bool
    {
        return self::canManagePrices();
    }

    public static function canCreate(): bool
    {
        return self::canManagePrices();
    }

    public static function canEdit(Model $record): bool
    {
        return self::canManagePrices();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return Builder<PriceSource> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['market', 'credentials']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('market_id')
                ->label('Market')
                ->relationship('market', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('code')
                ->required()
                ->maxLength(255)
                ->unique(
                    table: 'price_sources',
                    column: 'code',
                    ignoreRecord: true,
                    modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                        ->where('market_id', $get('market_id')),
                ),
            TextInput::make('name')->required()->maxLength(255),
            Select::make('type')
                ->options(PriceSourceType::options())
                ->required(),
            Select::make('status')
                ->options(PriceSourceStatus::options())
                ->default(PriceSourceStatus::default()->value)
                ->required(),
            Select::make('update_frequency')
                ->label('Update frequency')
                ->options(PriceSourceUpdateFrequency::options())
                ->default(PriceSourceUpdateFrequency::Manual->value),
            KeyValue::make('config_json')
                ->label('Source configuration')
                ->keyLabel('Config key')
                ->valueLabel('Value')
                ->default([])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('market.name')->label('Market')->searchable()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('update_frequency')->label('Frequency')->badge()->placeholder('Not set'),
                TextColumn::make('last_sync_at')->dateTime()->sortable()->placeholder('Never'),
                TextColumn::make('credentials_status')
                    ->label('Credentials')
                    ->state(fn (PriceSource $record): string => $record->credentials === null
                        ? 'Missing'
                        : 'Configured (masked)')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('market_id')->label('Market')->relationship('market', 'name'),
                SelectFilter::make('type')->options(PriceSourceType::options()),
                SelectFilter::make('status')->options(PriceSourceStatus::options()),
            ])
            ->recordActions([
                Action::make('triggerSync')
                    ->label('Trigger sync')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->requiresConfirmation()
                    ->action(fn (PriceSource $record): PriceSourceSyncLog => app(PriceSourceSyncService::class)
                        ->sync($record)),
                Action::make('syncLogs')
                    ->label('View logs')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn (PriceSource $record): string => PriceSourceSyncLogResource::getUrl('index', [
                        'tableFilters' => [
                            'price_source_id' => ['value' => $record->id],
                        ],
                    ])),
                Action::make('mappings')
                    ->label('View mappings')
                    ->icon(Heroicon::OutlinedLink)
                    ->url(fn (PriceSource $record): string => ExternalProductMappingResource::getUrl('index', [
                        'tableFilters' => [
                            'price_source_id' => ['value' => $record->id],
                        ],
                    ])),
                Action::make('credentials')
                    ->label('Update credentials')
                    ->icon(Heroicon::OutlinedKey)
                    ->modalDescription('Existing secret values are never displayed. Saving replaces the credential set.')
                    ->schema([
                        KeyValue::make('credentials')
                            ->keyLabel('Credential key')
                            ->valueLabel('Secret value')
                            ->required(),
                    ])
                    ->action(function (array $data, PriceSource $record): void {
                        $credentials = $data['credentials'] ?? [];

                        if (is_array($credentials)) {
                            app(PriceSourceCredentialService::class)->store($record, $credentials);
                        }
                    }),
                EditAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPriceSources::route('/'),
            'create' => Pages\CreatePriceSource::route('/create'),
            'edit' => Pages\EditPriceSource::route('/{record}/edit'),
        ];
    }

    private static function canManagePrices(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('prices.manage');
    }
}
