<?php

namespace App\Filament\Resources;

use App\Enums\ExternalProductMappingStatus;
use App\Filament\Resources\ExternalProductMappingResource\Pages;
use App\Models\ExternalProductMapping;
use App\Models\Market;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
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

final class ExternalProductMappingResource extends Resource
{
    protected static ?string $model = ExternalProductMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'Pricing';

    protected static ?string $navigationLabel = 'Product mappings';

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

    /** @return Builder<ExternalProductMapping> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'priceSource.market',
            'centralProduct',
            'approvedByUser',
            'rejectedByUser',
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('priceSource.name')->label('Price source')->searchable()->sortable(),
                TextColumn::make('priceSource.market.name')->label('Market')->sortable(),
                TextColumn::make('external_title')->label('External title')->searchable()->limit(70)->placeholder('None'),
                TextColumn::make('external_sku')->label('External SKU')->searchable()->placeholder('None'),
                TextColumn::make('centralProduct.name')->label('Central product')->searchable()->placeholder('Not mapped'),
                TextColumn::make('confidence')->numeric(decimalPlaces: 4)->sortable()->placeholder('Unknown'),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('price_source_id')
                    ->label('Price source')
                    ->relationship('priceSource', 'name'),
                SelectFilter::make('market_id')
                    ->label('Market')
                    ->options(fn (): array => Market::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('priceSource', fn (Builder $query): Builder => $query
                            ->where('market_id', $data['value']))
                        : $query),
                SelectFilter::make('status')->options(ExternalProductMappingStatus::options()),
                Filter::make('confidence')
                    ->schema([
                        TextInput::make('min')->numeric()->minValue(0)->maxValue(1),
                        TextInput::make('max')->numeric()->minValue(0)->maxValue(1),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(filled($data['min'] ?? null), fn (Builder $query): Builder => $query
                            ->where('confidence', '>=', $data['min']))
                        ->when(filled($data['max'] ?? null), fn (Builder $query): Builder => $query
                            ->where('confidence', '<=', $data['max']))),
            ])
            ->recordActions([ViewAction::make()])
            ->defaultSort('updated_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('priceSource.name')->label('Price source'),
            TextEntry::make('priceSource.market.name')->label('Market'),
            TextEntry::make('status')->badge(),
            TextEntry::make('confidence')->numeric(decimalPlaces: 4)->placeholder('Unknown'),
            TextEntry::make('external_product_id')->label('External product ID')->placeholder('None'),
            TextEntry::make('external_sku')->label('External SKU')->placeholder('None'),
            TextEntry::make('external_title')->label('External title')->placeholder('None')->columnSpanFull(),
            TextEntry::make('external_url')->label('External URL')->url(fn (?string $state): ?string => $state)->placeholder('None')->columnSpanFull(),
            TextEntry::make('centralProduct.name')->label('Central product')->placeholder('Not mapped'),
            TextEntry::make('approved_at')->dateTime()->placeholder('Not approved'),
            TextEntry::make('approvedByUser.name')->label('Approved by')->placeholder('None'),
            TextEntry::make('rejected_at')->dateTime()->placeholder('Not rejected'),
            TextEntry::make('rejectedByUser.name')->label('Rejected by')->placeholder('None'),
            TextEntry::make('notes')->placeholder('None')->columnSpanFull(),
            TextEntry::make('metadata')
                ->formatStateUsing(fn (mixed $state): string => (string) json_encode(
                    $state ?? [],
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
                ))
                ->copyable()
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExternalProductMappings::route('/'),
            'view' => Pages\ViewExternalProductMapping::route('/{record}'),
        ];
    }

    private static function canManagePrices(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->hasCatalogHubPermission('prices.manage');
    }
}
