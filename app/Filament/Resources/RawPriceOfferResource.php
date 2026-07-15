<?php

namespace App\Filament\Resources;

use App\Enums\RawPriceOfferStatus;
use App\Filament\Resources\RawPriceOfferResource\Pages;
use App\Models\ExternalProductMapping;
use App\Models\RawPriceOffer;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class RawPriceOfferResource extends Resource
{
    protected static ?string $model = RawPriceOffer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?string $navigationLabel = 'Raw price offers';

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
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /** @return Builder<RawPriceOffer> */
    public static function getEloquentQuery(): Builder
    {
        return RawPriceOffer::query()->with(['priceSource', 'syncLog']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priceSource.name')->label('Source')->searchable()->sortable(),
                TextColumn::make('external_product_id')->label('External product ID')->searchable()->placeholder('None'),
                TextColumn::make('external_sku')->label('External SKU')->searchable()->placeholder('None'),
                TextColumn::make('external_title')->label('External title')->searchable()->limit(70)->placeholder('None'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('fetched_at')->dateTime()->sortable(),
                TextColumn::make('error_message')->label('Error')->limit(80)->placeholder('None'),
            ])
            ->filters([
                SelectFilter::make('price_source_id')
                    ->label('Source')
                    ->relationship('priceSource', 'name'),
                SelectFilter::make('status')->options(RawPriceOfferStatus::options()),
                Filter::make('fetched_at')
                    ->schema([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['from'] ?? null), fn (Builder $query): Builder => $query
                            ->whereDate('fetched_at', '>=', $data['from']))
                        ->when(filled($data['until'] ?? null), fn (Builder $query): Builder => $query
                            ->whereDate('fetched_at', '<=', $data['until']))),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('fetched_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('priceSource.name')->label('Price source'),
            TextEntry::make('status')->badge(),
            TextEntry::make('fetched_at')->dateTime(),
            TextEntry::make('external_product_id')->label('External product ID')->placeholder('None'),
            TextEntry::make('external_sku')->label('External SKU')->placeholder('None'),
            TextEntry::make('external_title')->label('External title')->placeholder('None')->columnSpanFull(),
            TextEntry::make('error_message')->label('Error')->placeholder('None')->columnSpanFull(),
            TextEntry::make('syncLog.id')
                ->label('Sync log')
                ->url(fn (RawPriceOffer $record): ?string => $record->syncLog === null
                    ? null
                    : PriceSourceSyncLogResource::getUrl('view', ['record' => $record->syncLog])),
            TextEntry::make('related_mapping')
                ->label('Related mapping')
                ->state(fn (RawPriceOffer $record): string => ($mapping = self::mappingFor($record)) === null
                    ? 'Not mapped'
                    : "Mapping #{$mapping->id} ({$mapping->status->value})")
                ->url(fn (RawPriceOffer $record): ?string => ($mapping = self::mappingFor($record)) === null
                    ? null
                    : ExternalProductMappingResource::getUrl('view', ['record' => $mapping])),
            TextEntry::make('raw_payload_json')
                ->label('Raw payload')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->copyable()
                ->columnSpanFull(),
            TextEntry::make('normalized_payload_json')
                ->label('Normalized payload')
                ->formatStateUsing(fn (mixed $state): string => self::prettyJson($state))
                ->placeholder('Not normalized')
                ->copyable()
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRawPriceOffers::route('/'),
            'view' => Pages\ViewRawPriceOffer::route('/{record}'),
        ];
    }

    private static function mappingFor(RawPriceOffer $offer): ?ExternalProductMapping
    {
        $query = ExternalProductMapping::query()->where('price_source_id', $offer->price_source_id);

        $mapping = filled($offer->external_product_id)
            ? (clone $query)->where('external_product_id', $offer->external_product_id)->first()
            : null;

        if ($mapping === null && filled($offer->external_sku)) {
            $mapping = (clone $query)->where('external_sku', $offer->external_sku)->first();
        }

        return $mapping;
    }

    private static function prettyJson(mixed $state): string
    {
        return (string) json_encode(
            $state ?? [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    private static function canManagePrices(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('prices.manage');
    }
}
